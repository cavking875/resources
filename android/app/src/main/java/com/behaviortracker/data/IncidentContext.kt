package com.behaviortracker.data

enum class IncidentContext(val label: String, val description: String) {
    HANDOVER("Handover", "During custody handover"),
    BEFORE_SCHOOL("Before School", "Before school starts"),
    LEAVING_MUMS("Leaving Mum's", "When leaving Mum's house"),
    LEAVING_DADS("Leaving Dad's", "When leaving Dad's house"),
    ROUTINE_CHANGE("Routine Change", "When a routine was disrupted"),
    AFTER_CONTACT("After Contact", "After contact with someone specific"),
    OTHER("Other", "Another context")
}
