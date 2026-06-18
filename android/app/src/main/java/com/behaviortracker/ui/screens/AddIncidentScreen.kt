package com.behaviortracker.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.behaviortracker.data.BehaviorIncident
import com.behaviortracker.data.IncidentContext
import com.behaviortracker.ui.viewmodel.BehaviorViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AddIncidentScreen(
    viewModel: BehaviorViewModel,
    onBack: () -> Unit
) {
    var description by remember { mutableStateOf("") }
    var severity by remember { mutableIntStateOf(3) }
    var selectedContexts by remember { mutableStateOf(setOf<IncidentContext>()) }
    var notes by remember { mutableStateOf("") }
    var people by remember { mutableStateOf("") }

    val canSave = description.isNotBlank() && selectedContexts.isNotEmpty()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Log Incident") },
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
                            viewModel.addIncident(
                                BehaviorIncident(
                                    description = description.trim(),
                                    severity = severity,
                                    contexts = BehaviorIncident.contextsToString(selectedContexts),
                                    notes = notes.trim(),
                                    people = people.trim()
                                )
                            )
                            onBack()
                        },
                        enabled = canSave
                    ) {
                        Icon(Icons.Default.Check, contentDescription = "Save")
                    }
                }
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .padding(padding)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
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
                placeholder = { Text("Any triggers, what preceded it, how it resolved…") },
                modifier = Modifier.fillMaxWidth(),
                minLines = 3,
                maxLines = 8
            )

            if (!canSave) {
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
            val selected = ctx in selectedContexts
            FilterChip(
                selected = selected,
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
            val level = index + 1
            FilterChip(
                selected = severity == level,
                onClick = { onSeverityChange(level) },
                label = { Text(label, style = MaterialTheme.typography.labelSmall) },
                modifier = Modifier.weight(1f)
            )
        }
    }
}
