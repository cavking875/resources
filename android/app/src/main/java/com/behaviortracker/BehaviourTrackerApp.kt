package com.behaviortracker

import android.app.Application
import com.behaviortracker.data.BehaviorDatabase

class BehaviourTrackerApp : Application() {
    val database: BehaviorDatabase by lazy { BehaviorDatabase.getDatabase(this) }
}
