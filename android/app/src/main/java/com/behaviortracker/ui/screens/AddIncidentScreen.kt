@file:OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)

package com.behaviortracker.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.behaviortracker.data.BehaviorIncident
import com.behaviortracker.data.IncidentContext
import com.behaviortracker.ui.theme.SeverityHigh
import com.behaviortracker.ui.theme.SeverityLow
import com.behaviortracker.ui.theme.SeverityMild
import com.behaviortracker.ui.theme.SeverityMod
import com.behaviortracker.ui.theme.SeveritySevere
import com.behaviortracker.ui.viewmodel.BehaviorViewModel
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.*

@Composable
fun AddIncidentScreen(
    viewModel: BehaviorViewModel,
    editIncidentId: Int?,
    onBack: () -> Unit
) {
    val isEditMode = editIncidentId != null
    val scope = rememberCoroutineScope()

    var description by remember { mutableStateOf("") }
    var severity by remember { mutableIntStateOf(3) }
    var selectedContexts by remember { mutableStateOf(setOf<IncidentContext>()) }
    var notes by remember { mutableStateOf("") }
    var people by remember { mutableStateOf("") }
    var incidentTimestamp by remember { mutableLongStateOf(System.currentTimeMillis()) }
    var originalIncident by remember { mutableStateOf<BehaviorIncident?>(null) }
    var loaded by remember { mutableStateOf(!isEditMode) }

    LaunchedEffect(editIncidentId) {
        if (editIncidentId != null) {
            viewModel.getIncidentById(editIncidentId)?.let { incident ->
                originalIncident = incident
                description = incident.description
                severity = incident.severity
                selectedContexts = incident.contextList().toSet()
                notes = incident.notes
                people = incident.people
                incidentTimestamp = incident.timestamp
            }
            loaded = true
        }
    }

    var showDatePicker by remember { mutableStateOf(false) }
    var showTimePicker by remember { mutableStateOf(false) }

    val dateFormat = remember { SimpleDateFormat("EEE d MMM yyyy", Locale.getDefault()) }
    val timeFormat = remember { SimpleDateFormat("HH:mm", Locale.getDefault()) }

    val datePickerState = rememberDatePickerState(initialSelectedDateMillis = incidentTimestamp)
    val timePickerState = run {
        val cal = Calendar.getInstance().apply { timeInMillis = incidentTimestamp }
        rememberTimePickerState(
            initialHour = cal.get(Calendar.HOUR_OF_DAY),
            initialMinute = cal.get(Calendar.MINUTE),
            is24Hour = true
        )
    }

    if (showDatePicker) {
        DatePickerDialog(
            onDismissRequest = { showDatePicker = false },
            confirmButton = {
                TextButton(onClick = {
                    datePickerState.selectedDateMillis?.let { selectedMs ->
                        val cal = Calendar.getInstance().apply { timeInMillis = incidentTimestamp }
                        val sel = Calendar.getInstance().apply { timeInMillis = selectedMs }
                        cal.set(Calendar.YEAR, sel.get(Calendar.YEAR))
                        cal.set(Calendar.MONTH, sel.get(Calendar.MONTH))
                        cal.set(Calendar.DAY_OF_MONTH, sel.get(Calendar.DAY_OF_MONTH))
                        incidentTimestamp = cal.timeInMillis
                    }
                    showDatePicker = false
                }) { Text("OK") }
            },
            dismissButton = {
                TextButton(onClick = { showDatePicker = false }) { Text("Cancel") }
            }
        ) {
            DatePicker(state = datePickerState)
        }
    }

    if (showTimePicker) {
        AlertDialog(
            onDismissRequest = { showTimePicker = false },
            title = { Text("Select time") },
            text = {
                Box(modifier = Modifier.fillMaxWidth(), contentAlignment = Alignment.Center) {
                    TimePicker(state = timePickerState)
                }
            },
            confirmButton = {
                TextButton(onClick = {
                    val cal = Calendar.getInstance().apply { timeInMillis = incidentTimestamp }
                    cal.set(Calendar.HOUR_OF_DAY, timePickerState.hour)
                    cal.set(Calendar.MINUTE, timePickerState.minute)
                    incidentTimestamp = cal.timeInMillis
                    showTimePicker = false
                }) { Text("OK") }
            },
            dismissButton = {
                TextButton(onClick = { showTimePicker = false }) { Text("Cancel") }
            }
        )
    }

    val canSave = loaded && description.isNotBlank() && selectedContexts.isNotEmpty()

    fun save() {
        scope.launch {
            val incident = if (isEditMode && originalIncident != null) {
                originalIncident!!.copy(
                    description = description.trim(),
                    severity = severity,
                    contexts = BehaviorIncident.contextsToString(selectedContexts),
                    notes = notes.trim(),
                    people = people.trim(),
                    timestamp = incidentTimestamp
                )
            } else {
                BehaviorIncident(
                    description = description.trim(),
                    severity = severity,
                    contexts = BehaviorIncident.contextsToString(selectedContexts),
                    notes = notes.trim(),
                    people = people.trim(),
                    timestamp = incidentTimestamp
                )
            }
            if (isEditMode) viewModel.updateIncident(incident)
            else viewModel.addIncident(incident)
            onBack()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (isEditMode) "Edit incident" else "Log incident") },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface
                ),
                actions = {
                    Button(
                        onClick = { save() },
                        enabled = canSave,
                        modifier = Modifier.padding(end = 8.dp)
                    ) {
                        Text("Save")
                    }
                }
            )
        }
    ) { padding ->
        if (!loaded) {
            Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        } else {
            Column(
                modifier = Modifier
                    .padding(padding)
                    .verticalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp, vertical = 16.dp),
                verticalArrangement = Arrangement.spacedBy(20.dp)
            ) {
                // Section: When
                FormSection(title = "When did this happen?") {
                    Row(
                        horizontalArrangement = Arrangement.spacedBy(10.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        OutlinedCard(
                            modifier = Modifier
                                .weight(1f)
                                .clickable { showDatePicker = true }
                        ) {
                            Row(
                                modifier = Modifier.padding(14.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                Icon(
                                    Icons.Default.CalendarMonth,
                                    contentDescription = null,
                                    tint = MaterialTheme.colorScheme.primary,
                                    modifier = Modifier.size(20.dp)
                                )
                                Column {
                                    Text(
                                        "Date",
                                        style = MaterialTheme.typography.labelSmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                    Text(
                                        dateFormat.format(Date(incidentTimestamp)),
                                        style = MaterialTheme.typography.bodyMedium
                                    )
                                }
                            }
                        }
                        OutlinedCard(
                            modifier = Modifier
                                .weight(1f)
                                .clickable { showTimePicker = true }
                        ) {
                            Row(
                                modifier = Modifier.padding(14.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                Icon(
                                    Icons.Default.Schedule,
                                    contentDescription = null,
                                    tint = MaterialTheme.colorScheme.primary,
                                    modifier = Modifier.size(20.dp)
                                )
                                Column {
                                    Text(
                                        "Time",
                                        style = MaterialTheme.typography.labelSmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                    Text(
                                        timeFormat.format(Date(incidentTimestamp)),
                                        style = MaterialTheme.typography.bodyMedium
                                    )
                                }
                            }
                        }
                    }
                }

                // Section: What happened
                FormSection(title = "What happened? *") {
                    OutlinedTextField(
                        value = description,
                        onValueChange = { description = it },
                        placeholder = { Text("Describe the behaviour…") },
                        modifier = Modifier.fillMaxWidth(),
                        minLines = 3,
                        maxLines = 6
                    )
                }

                // Section: Context
                FormSection(
                    title = "Context *",
                    subtitle = "Select everything that applies"
                ) {
                    ContextSelector(
                        selectedContexts = selectedContexts,
                        onToggle = { ctx ->
                            selectedContexts = if (ctx in selectedContexts)
                                selectedContexts - ctx else selectedContexts + ctx
                        }
                    )
                }

                // Section: Severity
                FormSection(title = "How severe was it?") {
                    ColoredSeveritySelector(
                        severity = severity,
                        onSeverityChange = { severity = it }
                    )
                }

                // Section: Details
                FormSection(title = "Who was involved?") {
                    OutlinedTextField(
                        value = people,
                        onValueChange = { people = it },
                        placeholder = { Text("e.g. Mum, teacher, stepdad…") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                }

                FormSection(title = "Notes & possible triggers") {
                    OutlinedTextField(
                        value = notes,
                        onValueChange = { notes = it },
                        placeholder = { Text("What led up to this? How did it resolve?") },
                        modifier = Modifier.fillMaxWidth(),
                        minLines = 3,
                        maxLines = 8
                    )
                }

                if (!canSave) {
                    Text(
                        text = "* Description and at least one context are required to save.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                Spacer(Modifier.height(60.dp))
            }
        }
    }
}

@Composable
private fun FormSection(
    title: String,
    subtitle: String? = null,
    content: @Composable ColumnScope.() -> Unit
) {
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text(
            text = title,
            style = MaterialTheme.typography.titleSmall,
            color = MaterialTheme.colorScheme.primary
        )
        if (subtitle != null) {
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        content()
    }
}

@Composable
private fun ContextSelector(
    selectedContexts: Set<IncidentContext>,
    onToggle: (IncidentContext) -> Unit
) {
    FlowRow(
        horizontalArrangement = Arrangement.spacedBy(8.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        IncidentContext.entries.forEach { ctx ->
            FilterChip(
                selected = ctx in selectedContexts,
                onClick = { onToggle(ctx) },
                label = { Text(ctx.label) }
            )
        }
    }
}

@Composable
private fun ColoredSeveritySelector(severity: Int, onSeverityChange: (Int) -> Unit) {
    val items = listOf(
        Triple(1, "Mild", SeverityMild),
        Triple(2, "Low", SeverityLow),
        Triple(3, "Moderate", SeverityMod),
        Triple(4, "High", SeverityHigh),
        Triple(5, "Severe", SeveritySevere)
    )
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(6.dp)
    ) {
        items.forEach { (level, label, color) ->
            val selected = severity == level
            Surface(
                modifier = Modifier
                    .weight(1f)
                    .clickable { onSeverityChange(level) },
                color = if (selected) color else color.copy(alpha = 0.12f),
                shape = MaterialTheme.shapes.small
            ) {
                Box(
                    contentAlignment = Alignment.Center,
                    modifier = Modifier.padding(vertical = 12.dp, horizontal = 4.dp)
                ) {
                    Text(
                        text = label,
                        style = MaterialTheme.typography.labelSmall,
                        color = if (selected) Color.White else color,
                        textAlign = TextAlign.Center
                    )
                }
            }
        }
    }
}
