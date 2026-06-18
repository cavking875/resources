package com.behaviortracker.data

import kotlinx.coroutines.flow.Flow

class BehaviorRepository(private val dao: BehaviorDao) {
    val allIncidents: Flow<List<BehaviorIncident>> = dao.getAllIncidents()

    suspend fun insertIncident(incident: BehaviorIncident) = dao.insertIncident(incident)

    suspend fun deleteIncident(incident: BehaviorIncident) = dao.deleteIncident(incident)
}
