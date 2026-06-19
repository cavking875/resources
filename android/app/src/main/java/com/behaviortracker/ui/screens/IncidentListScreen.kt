@file:OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)

package com.behaviortracker.ui.screens

import android.content.Intent
import androidx.compose.foundation.background
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.behaviortracker.data.BehaviorIncident
import com.behaviortracker.data.IncidentContext
import com.behaviortracker.ui.theme.SeverityHigh
import com.behaviortracker.ui.theme.SeverityLow
import com.behaviortracker.ui.theme.SeverityMild
import com.behaviortracker.ui.theme.SeverityMod
import com.behaviortracker.ui.theme.SeveritySevere
import com.behaviortracker.ui.viewmodel.BehaviorViewModel
import java.text.SimpleDateFormat
import java.util.*

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
                title = { Text("Behaviour Log", style = MaterialTheme.typography.titleLarge) },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface
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
            ExtendedFloatingActionButton(
                onClick = onAddClick,
                icon = { Icon(Icons.Default.Add, contentDescription = null) },
                text = { Text("Log incident") }
            )
        }
    ) { padding ->
        Column(modifier = Modifier.padding(padding)) {
            if (allIncidents.isNotEmpty()) {
                StatsHeader(incidents = allIncidents)
            }
            ContextFilterRow(
                selectedContext = filterContext,
                onFilterSelected = { viewModel.setFilter(it) }
            )
            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)

            if (incidents.isEmpty()) {
                EmptyState(hasFilter = filterContext != null)
            } else {
                LazyColumn(
                    contentPadding = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    items(incidents, key = { it.id }) { incident ->
                        IncidentCard(
                            incident = incident,
                            onEdit = { onEditClick(incident.id) },
                            onDelete = { viewModel.deleteIncident(incident) }
                        )
                    }
                    // Space for FAB
                    item { Spacer(Modifier.height(80.dp)) }
                }
            }
        }
    }
}

@Composable
private fun StatsHeader(incidents: List<BehaviorIncident>) {
    val dateFormat = remember { SimpleDateFormat("d MMM", Locale.getDefault()) }
    val lastDate = remember(incidents) {
        incidents.maxByOrNull { it.timestamp }?.let { dateFormat.format(Date(it.timestamp)) }
    }
    val avgSeverity = remember(incidents) {
        incidents.sumOf { it.severity }.toFloat() / incidents.size
    }

    Surface(color = MaterialTheme.colorScheme.primaryContainer) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp, vertical = 12.dp),
            horizontalArrangement = Arrangement.spacedBy(32.dp)
        ) {
            QuickStat(label = "TOTAL", value = "${incidents.size}")
            if (lastDate != null) QuickStat(label = "LAST", value = lastDate)
            QuickStat(label = "AVG SEVERITY", value = "%.1f / 5".format(avgSeverity))
        }
    }
}

@Composable
private fun QuickStat(label: String, value: String) {
    Column {
        Text(
            text = label,
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.65f)
        )
        Text(
            text = value,
            style = MaterialTheme.typography.titleMedium,
            color = MaterialTheme.colorScheme.onPrimaryContainer
        )
    }
}

@Composable
private fun EmptyState(hasFilter: Boolean) {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .padding(40.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(
                text = if (hasFilter) "No incidents match\nthis filter" else "Nothing logged yet",
                style = MaterialTheme.typography.headlineSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
            Spacer(Modifier.height(8.dp))
            Text(
                text = if (hasFilter)
                    "Try selecting a different filter or All."
                else
                    "Tap the button below to log the first incident.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }
    }
}

@Composable
private fun ContextFilterRow(
    selectedContext: IncidentContext?,
    onFilterSelected: (IncidentContext?) -> Unit
) {
    LazyRow(
        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 10.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        item {
            FilterChip(
                selected = selectedContext == null,
                onClick = { onFilterSelected(null) },
                label = { Text("All") }
            )
        }
        items(IncidentContext.entries) { ctx ->
            FilterChip(
                selected = selectedContext == ctx,
                onClick = { onFilterSelected(ctx) },
                label = { Text(ctx.label) }
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
    val dateFormat = remember { SimpleDateFormat("EEE d MMM · HH:mm", Locale.getDefault()) }
    val sevColor = severityColor(incident.severity)
    val sevLabel = severityLabel(incident.severity)

    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            title = { Text("Delete this incident?") },
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

    ElevatedCard(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onEdit() },
        elevation = CardDefaults.elevatedCardElevation(defaultElevation = 1.dp)
    ) {
        Row(modifier = Modifier.height(IntrinsicSize.Min)) {
            // Severity strip
            Box(
                modifier = Modifier
                    .width(4.dp)
                    .fillMaxHeight()
                    .background(sevColor)
            )

            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 12.dp, top = 12.dp, bottom = 12.dp, end = 4.dp)
            ) {
                // Header row: date + severity badge
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = dateFormat.format(Date(incident.timestamp)),
                        style = MaterialTheme.typography.labelMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Surface(
                        color = sevColor.copy(alpha = 0.12f),
                        shape = MaterialTheme.shapes.extraSmall
                    ) {
                        Text(
                            text = sevLabel,
                            style = MaterialTheme.typography.labelSmall,
                            color = sevColor,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                        )
                    }
                }

                Spacer(Modifier.height(6.dp))
                Text(
                    text = incident.description,
                    style = MaterialTheme.typography.bodyMedium,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis
                )

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
                    Spacer(Modifier.height(2.dp))
                    Text(
                        text = incident.notes,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            }

            IconButton(
                onClick = { showDeleteDialog = true },
                modifier = Modifier.align(Alignment.Top)
            ) {
                Icon(
                    Icons.Default.Delete,
                    contentDescription = "Delete",
                    tint = MaterialTheme.colorScheme.error.copy(alpha = 0.45f),
                    modifier = Modifier.size(18.dp)
                )
            }
        }
    }
}

internal fun severityColor(severity: Int): Color = when (severity) {
    1 -> SeverityMild
    2 -> SeverityLow
    3 -> SeverityMod
    4 -> SeverityHigh
    5 -> SeveritySevere
    else -> Color.Gray
}

internal fun severityLabel(severity: Int): String = when (severity) {
    1 -> "Mild"
    2 -> "Low"
    3 -> "Moderate"
    4 -> "High"
    5 -> "Severe"
    else -> "?"
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
            appendLine("Severity:   ${incident.severity}/5 (${severityLabel(incident.severity)})")
            if (incident.contexts.isNotBlank()) {
                appendLine("Context:    ${incident.contextList().joinToString(", ") { it.label }}")
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
