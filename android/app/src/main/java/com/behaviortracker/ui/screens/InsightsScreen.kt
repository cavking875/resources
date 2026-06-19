package com.behaviortracker.ui.screens

import android.content.Intent
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.behaviortracker.data.IncidentContext
import com.behaviortracker.ui.viewmodel.BehaviorViewModel
import java.text.SimpleDateFormat
import java.util.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun InsightsScreen(viewModel: BehaviorViewModel) {
    val contextCounts by viewModel.contextCounts.collectAsStateWithLifecycle()
    val allIncidents by viewModel.allIncidents.collectAsStateWithLifecycle()
    val total = allIncidents.size
    val context = LocalContext.current

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Pattern Insights") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                ),
                actions = {
                    if (total > 0) {
                        IconButton(onClick = {
                            val text = buildInsightsSummary(contextCounts, total)
                            val intent = Intent(Intent.ACTION_SEND).apply {
                                type = "text/plain"
                                putExtra(Intent.EXTRA_SUBJECT, "Behaviour Patterns Summary")
                                putExtra(Intent.EXTRA_TEXT, text)
                            }
                            context.startActivity(
                                Intent.createChooser(intent, "Share Insights Summary")
                            )
                        }) {
                            Icon(Icons.Default.Share, contentDescription = "Share summary")
                        }
                    }
                }
            )
        }
    ) { padding ->
        if (total == 0) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding)
                    .padding(32.dp),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = "No data yet.\nLog some incidents and patterns will appear here.",
                    style = MaterialTheme.typography.bodyLarge,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    textAlign = TextAlign.Center
                )
            }
        } else {
            LazyColumn(
                modifier = Modifier.padding(padding),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                item {
                    Text(
                        text = "$total incident${if (total == 1) "" else "s"} logged",
                        style = MaterialTheme.typography.titleMedium
                    )
                    Spacer(Modifier.height(4.dp))
                    Text(
                        text = "Breakdown by context",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                val sorted = contextCounts.entries
                    .sortedByDescending { it.value }
                    .filter { it.value > 0 }

                if (sorted.isEmpty()) {
                    item {
                        Text(
                            text = "No context data to display yet.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                } else {
                    val maxCount = sorted.first().value.coerceAtLeast(1)
                    items(sorted) { (ctx, count) ->
                        ContextBar(context = ctx, count = count, total = total, maxCount = maxCount)
                    }
                }

                item { PatternSummary(contextCounts = contextCounts, total = total) }
            }
        }
    }
}

@Composable
private fun ContextBar(
    context: IncidentContext,
    count: Int,
    total: Int,
    maxCount: Int
) {
    val fraction = count.toFloat() / maxCount
    val percentage = if (total > 0) (count * 100 / total) else 0

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(text = context.label, style = MaterialTheme.typography.bodyMedium)
                Text(
                    text = "$count ($percentage%)",
                    style = MaterialTheme.typography.labelLarge,
                    color = MaterialTheme.colorScheme.primary
                )
            }
            Spacer(Modifier.height(8.dp))
            LinearProgressIndicator(
                progress = { fraction },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(8.dp),
                color = MaterialTheme.colorScheme.primary,
                trackColor = MaterialTheme.colorScheme.outlineVariant
            )
        }
    }
}

@Composable
private fun PatternSummary(
    contextCounts: Map<IncidentContext, Int>,
    total: Int
) {
    val topContext = contextCounts.maxByOrNull { it.value } ?: return
    if (topContext.value == 0) return

    val topPercent = topContext.value * 100 / total.coerceAtLeast(1)

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.primaryContainer
        )
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = "Most common pattern",
                style = MaterialTheme.typography.titleSmall,
                color = MaterialTheme.colorScheme.onPrimaryContainer
            )
            Spacer(Modifier.height(4.dp))
            Text(
                text = "${topContext.key.label} — $topPercent% of incidents",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onPrimaryContainer
            )
            Spacer(Modifier.height(4.dp))
            Text(
                text = topContext.key.description,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.7f)
            )
        }
    }
}

private fun buildInsightsSummary(
    contextCounts: Map<IncidentContext, Int>,
    total: Int
): String {
    val dateFormat = SimpleDateFormat("dd MMM yyyy", Locale.getDefault())
    return buildString {
        appendLine("BEHAVIOUR PATTERNS SUMMARY")
        appendLine("Generated: ${dateFormat.format(Date())}")
        appendLine("Total incidents on record: $total")
        appendLine()
        appendLine("Breakdown by context:")
        contextCounts.entries
            .sortedByDescending { it.value }
            .filter { it.value > 0 }
            .forEach { (ctx, count) ->
                val pct = count * 100 / total
                appendLine("  ${ctx.label}: $count incident${if (count == 1) "" else "s"} ($pct%)")
            }
        appendLine()
        val top = contextCounts.maxByOrNull { it.value }
        if (top != null && top.value > 0) {
            val pct = top.value * 100 / total
            appendLine("Most frequent: ${top.key.label} ($pct% of incidents)")
        }
    }
}
