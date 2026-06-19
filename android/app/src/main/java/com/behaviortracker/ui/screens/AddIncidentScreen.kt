package com.behaviortracker.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.behaviortracker.data.BehaviorIncident
import com.behaviortracker.data.IncidentContext
import com.behaviortracker.ui.viewmodel.BehaviorViewModel
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.*

@OptIn(ExperimentalMaterial3Api::class)
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

    // Load existing incident in edit mode
    LaunchedEffect(editIncidentId) {
        if (editIncidentId != null) {
            val incident = viewModel.getIncidentById(editIncidentId)
            if (incident != null) {
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

    val dateFormat = remember { SimpleDateFormat("EEE dd MMM yyyy", Locale.getDefault()) }
    val timeFormat = remember { SimpleDateFormat("HH:mm", Locale.getDefault()) }

    val datePickerState = rememberDatePickerState(
        initialSelectedDateMillis = incidentTimestamp
    )
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
                        val selectedCal = Calendar.getInstance().apply { timeInMillis = selectedMs }
                        cal.set(Calendar.YEAR, selectedCal.get(Calendar.YEAR))
                        cal.set(Calendar.MONTH, selectedCal.get(Calendar.MONTH))
                        cal.set(Calendar.DAY_OF_MONTH, selectedCal.get(Calendar.DAY_OF_MONTH))
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

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (isEditMode) "Edit Incident" else "Log Incident") },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                ),
                actions = {
                    IconButton(
                        onClick = {
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
                        },
                        enabled = canSave
                    ) {
                        Icon(Icons.Default.Check, contentDescription = "Save")
                    }
                }
            )
        }
    ) { padding ->
        if (!loaded) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator()
            }
        } else {
            Column(
                modifier = Modifier
                    .padding(padding)
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Date & time pickers
                Row(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    OutlinedCard(
                        modifier = Modifier
                            .weight(1f)
                            .clickable { showDatePicker = true }
                    ) {
                        Row(
                            modifier = Modifier.padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Icon(
                                Icons.Default.CalendarMonth,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(18.dp)
                            )
                            Column {
                                Text("Date", style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant)
                                Text(dateFormat.format(Date(incidentTimestamp)),
                                    style = MaterialTheme.typography.bodySmall)
                            }
                        }
                    }
                    OutlinedCard(
                        modifier = Modifier
                            .weight(1f)
                            .clickable { showTimePicker = true }
                    ) {
                        Row(
                            modifier = Modifier.padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Icon(
                                Icons.Default.Schedule,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(18.dp)
                            )
                            Column {
                                Text("Time", style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant)
                                Text(timeFormat.format(Date(incidentTimestamp)),
                                    style = MaterialTheme.typography.bodySmall)
                            }
                        }
                    }
                }

                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("What happened? *") },
                    placeholder = { Text("Describe the behaviour…") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 3,
                    maxLines = 6
                )

                SectionLabel("When / context *")
                Text(
                    text = "Select all that apply",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                ContextSelector(
                    selectedContexts = selectedContexts,
                    onToggle = { ctx ->
                        selectedContexts = if (ctx in selectedContexts)
                            selectedContexts - ctx else selectedContexts + ctx
                    }
                )

                SectionLabel("Severity")
                SeveritySelector(severity = severity, onSeverityChange = { severity = it })

                OutlinedTextField(
                    value = people,
                    onValueChange = { people = it },
                    label = { Text("Who was involved / present?") },
                    placeholder = { Text("e.g. Mum, teacher, stepdad…") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
                )

                OutlinedTextField(
                    value = notes,
                    onValueChange = { notes = it },
                    label = { Text("Additional notes / possible reasons") },
                    placeholder = { Text("Triggers, what preceded it, how it resolved…") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 3,
                    maxLines = 8
                )

                if (!canSave && loaded) {
                    Text(
                        text = "* Description and at least one context are required.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                Spacer(Modifier.height(80.dp))
            }
        }
    }
}

@Composable
private fun SectionLabel(text: String) {
    Text(
        text = text,
        style = MaterialTheme.typography.titleSmall,
        color = MaterialTheme.colorScheme.onBackground
    )
}

@OptIn(ExperimentalLayoutApi::class)
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
private fun SeveritySelector(severity: Int, onSeverityChange: (Int) -> Unit) {
    val labels = listOf("Mild", "Low", "Moderate", "High", "Severe")
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        labels.forEachIndexed { index, label ->
            FilterChip(
                selected = severity == index + 1,
                onClick = { onSeverityChange(index + 1) },
                label = { Text(label, style = MaterialTheme.typography.labelSmall) },
                modifier = Modifier.weight(1f)
            )
        }
    }
}
