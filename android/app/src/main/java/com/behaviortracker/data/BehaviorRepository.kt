package com.behaviortracker.data

import kotlinx.coroutines.flow.Flow

class BehaviorRepository(private val dao: BehaviorDao) {
    val allIncidents: Flow<List<BehaviorIncident>> = dao.getAllIncidents()

    suspend fun insertIncident(incident: BehaviorIncident) = dao.insertIncident(incident)
    suspend fun updateIncident(incident: BehaviorIncident) = dao.updateIncident(incident)
    suspend fun deleteIncident(incident: BehaviorIncident) = dao.deleteIncident(incident)
    suspend fun getIncidentById(id: Int): BehaviorIncident? = dao.getIncidentById(id)
}
