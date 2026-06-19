@file:OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)

package com.behaviortracker.ui.screens

import android.content.Intent
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.behaviortracker.data.BehaviorIncident
import com.behaviortracker.data.IncidentContext
import com.behaviortracker.ui.viewmodel.BehaviorViewModel
import java.text.SimpleDateFormat
import java.util.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun IncidentListScreen(
    viewModel: BehaviorViewModel,
    onAddClick: () -> Unit,
    onEditClick: (Int) -> Unit
) {
    val incidents by viewModel.filteredIncidents.collectAsStateWithLifecycle()
    val allIncidents by viewModel.allIncidents.collectAsStateWithLifecycle()
    val filterContext by viewModel.filterContext.collectAsStateWithLifecycle()
    val context = LocalContext.current

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Behaviour Log") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                ),
                actions = {
                    if (allIncidents.isNotEmpty()) {
                        IconButton(onClick = {
                            val text = buildExportText(allIncidents)
                            val intent = Intent(Intent.ACTION_SEND).apply {
                                type = "text/plain"
                                putExtra(Intent.EXTRA_SUBJECT, "Behaviour Log Export")
                                putExtra(Intent.EXTRA_TEXT, text)
                            }
                            context.startActivity(Intent.createChooser(intent, "Share Behaviour Log"))
                        }) {
                            Icon(Icons.Default.Share, contentDescription = "Export / Share")
                        }
                    }
                }
            )
        },
        floatingActionButton = {
            FloatingActionButton(onClick = onAddClick) {
                Icon(Icons.Default.Add, contentDescription = "Log incident")
            }
        }
    ) { padding ->
        Column(modifier = Modifier.padding(padding)) {
            ContextFilterRow(
                selectedContext = filterContext,
                onFilterSelected = { viewModel.setFilter(it) }
            )

            if (incidents.isEmpty()) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(32.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = if (filterContext == null)
                            "No incidents logged yet.\nTap + to add one."
                        else
                            "No incidents match this filter.",
                        style = MaterialTheme.typography.bodyLarge,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        textAlign = TextAlign.Center
                    )
                }
            } else {
                LazyColumn(
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    items(incidents, key = { it.id }) { incident ->
                        IncidentCard(
                            incident = incident,
                            onEdit = { onEditClick(incident.id) },
                            onDelete = { viewModel.deleteIncident(incident) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun ContextFilterRow(
    selectedContext: IncidentContext?,
    onFilterSelected: (IncidentContext?) -> Unit
) {
    LazyRow(
        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        item {
            FilterChip(
                selected = selectedContext == null,
                onClick = { onFilterSelected(null) },
                label = { Text("All") }
            )
        }
        items(IncidentContext.entries) { context ->
            FilterChip(
                selected = selectedContext == context,
                onClick = { onFilterSelected(context) },
                label = { Text(context.label) }
            )
        }
    }
}

@Composable
fun IncidentCard(
    incident: BehaviorIncident,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    var showDeleteDialog by remember { mutableStateOf(false) }
    val dateFormat = remember { SimpleDateFormat("dd MMM yyyy  HH:mm", Locale.getDefault()) }

    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            title = { Text("Delete incident?") },
            text = { Text("This cannot be undone.") },
            confirmButton = {
                TextButton(onClick = { onDelete(); showDeleteDialog = false }) {
                    Text("Delete", color = MaterialTheme.colorScheme.error)
                }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteDialog = false }) { Text("Cancel") }
            }
        )
    }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onEdit() },
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant
        )
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = dateFormat.format(Date(incident.timestamp)),
                        style = MaterialTheme.typography.labelMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Spacer(Modifier.height(4.dp))
                    Text(
                        text = incident.description,
                        style = MaterialTheme.typography.bodyLarge,
                        maxLines = 3,
                        overflow = TextOverflow.Ellipsis
                    )
                }
                IconButton(onClick = { showDeleteDialog = true }) {
                    Icon(
                        Icons.Default.Delete,
                        contentDescription = "Delete",
                        tint = MaterialTheme.colorScheme.error.copy(alpha = 0.7f)
                    )
                }
            }

            Spacer(Modifier.height(8.dp))
            SeverityIndicator(severity = incident.severity)

            val contextList = incident.contextList()
            if (contextList.isNotEmpty()) {
                Spacer(Modifier.height(8.dp))
                FlowRow(
                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                    verticalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    contextList.forEach { ctx ->
                        SuggestionChip(
                            onClick = {},
                            label = { Text(ctx.label, style = MaterialTheme.typography.labelSmall) }
                        )
                    }
                }
            }

            if (incident.people.isNotBlank()) {
                Spacer(Modifier.height(4.dp))
                Text(
                    text = "People: ${incident.people}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            if (incident.notes.isNotBlank()) {
                Spacer(Modifier.height(4.dp))
                Text(
                    text = incident.notes,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
    }
}

@Composable
private fun SeverityIndicator(severity: Int) {
    val label = when (severity) {
        1 -> "Mild"; 2 -> "Low"; 3 -> "Moderate"; 4 -> "High"; 5 -> "Severe"
        else -> "Unknown"
    }
    val color = when (severity) {
        1 -> MaterialTheme.colorScheme.tertiary
        2 -> MaterialTheme.colorScheme.secondary
        3 -> MaterialTheme.colorScheme.primary
        4 -> MaterialTheme.colorScheme.error.copy(alpha = 0.7f)
        5 -> MaterialTheme.colorScheme.error
        else -> MaterialTheme.colorScheme.outline
    }
    Row(
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp)
    ) {
        Text(
            text = "Severity:",
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        repeat(5) { i ->
            Surface(
                modifier = Modifier.size(10.dp),
                shape = MaterialTheme.shapes.extraSmall,
                color = if (i < severity) color else MaterialTheme.colorScheme.outline.copy(alpha = 0.3f)
            ) {}
        }
        Text(text = label, style = MaterialTheme.typography.labelSmall, color = color)
    }
}

private fun buildExportText(incidents: List<BehaviorIncident>): String {
    val dateFormat = SimpleDateFormat("dd MMM yyyy HH:mm", Locale.getDefault())
    return buildString {
        appendLine("BEHAVIOUR LOG")
        appendLine("Generated: ${dateFormat.format(Date())}")
        appendLine("Total incidents: ${incidents.size}")
        appendLine("=".repeat(50))
        appendLine()

        incidents.forEach { incident ->
            appendLine("Date:       ${dateFormat.format(Date(incident.timestamp))}")
            appendLine("Severity:   ${incident.severity}/5")
            if (incident.contexts.isNotBlank()) {
                val ctxLabels = incident.contextList().joinToString(", ") { it.label }
                appendLine("Context:    $ctxLabels")
            }
            if (incident.people.isNotBlank()) {
                appendLine("People:     ${incident.people}")
            }
            appendLine("What:       ${incident.description}")
            if (incident.notes.isNotBlank()) {
                appendLine("Notes:      ${incident.notes}")
            }
            appendLine("-".repeat(50))
            appendLine()
        }
    }
}
