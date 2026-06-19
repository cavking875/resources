package com.behaviortracker.data

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "incidents")
data class BehaviorIncident(
    @PrimaryKey(autoGenerate = true) val id: Int = 0,
    val timestamp: Long = System.currentTimeMillis(),
    val description: String,
    val severity: Int,
    val contexts: String,
    val notes: String = "",
    val people: String = ""
) {
    fun contextList(): List<IncidentContext> =
        contexts.split(",")
            .mapNotNull { name -> IncidentContext.entries.find { it.name == name.trim() } }

    companion object {
        fun contextsToString(list: Set<IncidentContext>): String =
            list.joinToString(",") { it.name }
    }
}
