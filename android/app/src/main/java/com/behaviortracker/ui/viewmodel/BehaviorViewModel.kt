package com.behaviortracker.ui.viewmodel

import androidx.lifecycle.*
import com.behaviortracker.data.*
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

class BehaviorViewModel(private val dao: BehaviorDao) : ViewModel() {

    private val repository = BehaviorRepository(dao)

    private val _filterContext = MutableStateFlow<IncidentContext?>(null)
    val filterContext: StateFlow<IncidentContext?> = _filterContext.asStateFlow()

    val allIncidents: StateFlow<List<BehaviorIncident>> = repository.allIncidents
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), emptyList())

    val filteredIncidents: StateFlow<List<BehaviorIncident>> =
        combine(allIncidents, _filterContext) { incidents, filter ->
            if (filter == null) incidents
            else incidents.filter { it.contexts.contains(filter.name) }
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), emptyList())

    val contextCounts: StateFlow<Map<IncidentContext, Int>> = allIncidents.map { incidents ->
        IncidentContext.entries.associateWith { ctx ->
            incidents.count { it.contexts.contains(ctx.name) }
        }
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), emptyMap())

    fun setFilter(context: IncidentContext?) {
        _filterContext.value = context
    }

    fun addIncident(incident: BehaviorIncident) = viewModelScope.launch {
        repository.insertIncident(incident)
    }

    fun deleteIncident(incident: BehaviorIncident) = viewModelScope.launch {
        repository.deleteIncident(incident)
    }
}

class BehaviorViewModelFactory(private val dao: BehaviorDao) : ViewModelProvider.Factory {
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(BehaviorViewModel::class.java)) {
            @Suppress("UNCHECKED_CAST")
            return BehaviorViewModel(dao) as T
        }
        throw IllegalArgumentException("Unknown ViewModel class")
    }
}
