package com.behaviortracker.data

import androidx.room.*
import kotlinx.coroutines.flow.Flow

@Dao
interface BehaviorDao {
    @Query("SELECT * FROM incidents ORDER BY timestamp DESC")
    fun getAllIncidents(): Flow<List<BehaviorIncident>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertIncident(incident: BehaviorIncident)

    @Delete
    suspend fun deleteIncident(incident: BehaviorIncident)

    @Query("SELECT * FROM incidents WHERE id = :id")
    suspend fun getIncidentById(id: Int): BehaviorIncident?
}
