package com.behaviortracker.data

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase

@Database(entities = [BehaviorIncident::class], version = 1, exportSchema = false)
abstract class BehaviorDatabase : RoomDatabase() {
    abstract fun behaviorDao(): BehaviorDao

    companion object {
        @Volatile
        private var INSTANCE: BehaviorDatabase? = null

        fun getDatabase(context: Context): BehaviorDatabase =
            INSTANCE ?: synchronized(this) {
                Room.databaseBuilder(
                    context.applicationContext,
                    BehaviorDatabase::class.java,
                    "behaviour_tracker_db"
                ).build().also { INSTANCE = it }
            }
    }
}
