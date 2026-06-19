@file:OptIn(ExperimentalMaterial3Api::class)

package com.behaviortracker.ui.screens

import android.content.Intent
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.TrendingDown
import androidx.compose.material.icons.filled.TrendingFlat
import androidx.compose.material.icons.filled.TrendingUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.behaviortracker.data.IncidentContext
import com.behaviortracker.ui.viewmodel.BehaviorViewModel
import java.text.SimpleDateFormat
import java.util.*

@Composable
fun InsightsScreen(viewModel: BehaviorViewModel) {
    val contextCounts by viewModel.contextCounts.collectAsStateWithLifecycle()
    val allIncidents by viewModel.allIncidents.collectAsStateWithLifecycle()
    val context = LocalContext.current
    val total = allIncidents.size

    val weekMs = 7 * 24 * 60 * 60 * 1000L
    val now = remember { System.currentTimeMillis() }
    val thisWeekCount = remember(allIncidents) {
        allIncidents.count { it.timestamp >= now - weekMs }
    }
    val lastWeekCount = remember(allIncidents) {
        allIncidents.count { it.timestamp >= now - 2 * weekMs && it.timestamp < now - weekMs }
    }
    val avgSeverity = remember(allIncidents) {
        if (allIncidents.isEmpty()) 0f else allIncidents.sumOf { it.severity }.toFloat() / allIncidents.size
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Insights", style = MaterialTheme.typography.titleLarge) },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface
                ),
                actions = {
                    if (total > 0) {
                        IconButton(onClick = {
                            val text = buildInsightsSummary(contextCounts, total, thisWeekCount, lastWeekCount, avgSeverity)
                            val intent = Intent(Intent.ACTION_SEND).apply {
                                type = "text/plain"
                                putExtra(Intent.EXTRA_SUBJECT, "Behaviour Patterns Summary")
                                putExtra(Intent.EXTRA_TEXT, text)
                            }
                            context.startActivity(Intent.createChooser(intent, "Share Insights"))
                        }) {
                            Icon(Icons.Default.Share, contentDescription = "Share insights")
                        }
                    }
                }
            )
        }
    ) { padding ->
        if (total == 0) {
            EmptyInsights(modifier = Modifier.padding(padding))
        } else {
            LazyColumn(
                modifier = Modifier.padding(padding),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                item {
                    SummaryCard(
                        total = total,
                        thisWeek = thisWeekCount,
                        lastWeek = lastWeekCount,
                        avgSeverity = avgSeverity
                    )
                }

                item {
                    Text(
                        text = "Breakdown by context",
                        style = MaterialTheme.typography.titleMedium,
                        modifier = Modifier.padding(top = 4.dp, bottom = 2.dp)
                    )
                }

                val sorted = contextCounts.entries
                    .sortedByDescending { it.value }
                    .filter { it.value > 0 }

                if (sorted.isEmpty()) {
                    item {
                        Text(
                            "No context data yet. Add contexts when logging incidents.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                } else {
                    val maxCount = sorted.first().value.coerceAtLeast(1)
                    items(sorted) { (ctx, count) ->
                        ContextBar(ctx = ctx, count = count, total = total, maxCount = maxCount)
                    }

                    item {
                        PatternSummary(
                            topEntry = sorted.first(),
                            total = total
                        )
                    }
                }

                item { Spacer(Modifier.height(16.dp)) }
            }
        }
    }
}

@Composable
private fun SummaryCard(total: Int, thisWeek: Int, lastWeek: Int, avgSeverity: Float) {
    val trendIcon = when {
        thisWeek > lastWeek -> Icons.Default.TrendingUp
        thisWeek < lastWeek -> Icons.Default.TrendingDown
        else -> Icons.Default.TrendingFlat
    }
    val trendColor = when {
        thisWeek > lastWeek -> MaterialTheme.colorScheme.error
        thisWeek < lastWeek -> Color(0xFF2E7D32)
        else -> MaterialTheme.colorScheme.onSurfaceVariant
    }
    val trendText = when {
        lastWeek == 0 && thisWeek > 0 -> "$thisWeek this week"
        thisWeek > lastWeek -> "$thisWeek this week (+${thisWeek - lastWeek})"
        thisWeek < lastWeek -> "$thisWeek this week (-${lastWeek - thisWeek})"
        else -> "$thisWeek this week (same)"
    }

    ElevatedCard(modifier = Modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(20.dp),
            horizontalArrangement = Arrangement.SpaceEvenly
        ) {
            InsightStat(label = "Total", value = total.toString())
            VerticalDivider(modifier = Modifier.height(52.dp))
            InsightStat(label = "This week", value = thisWeek.toString()) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(2.dp)
                ) {
                    Icon(trendIcon, contentDescription = null, tint = trendColor, modifier = Modifier.size(14.dp))
                    Text(trendText, style = MaterialTheme.typography.labelSmall, color = trendColor)
                }
            }
            VerticalDivider(modifier = Modifier.height(52.dp))
            InsightStat(label = "Avg severity", value = "%.1f".format(avgSeverity))
        }
    }
}

@Composable
private fun InsightStat(
    label: String,
    value: String,
    sub: (@Composable () -> Unit)? = null
) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Text(
            text = value,
            style = MaterialTheme.typography.headlineSmall,
            color = MaterialTheme.colorScheme.primary
        )
        if (sub != null) sub()
    }
}

@Composable
private fun ContextBar(ctx: IncidentContext, count: Int, total: Int, maxCount: Int) {
    val fraction = count.toFloat() / maxCount
    val percentage = if (total > 0) count * 100 / total else 0
    val barColor = contextColor(ctx)

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Bottom
            ) {
                Text(text = ctx.label, style = MaterialTheme.typography.bodyMedium)
                Text(
                    text = "$count  ·  $percentage%",
                    style = MaterialTheme.typography.labelMedium,
                    color = barColor
                )
            }
            Spacer(Modifier.height(8.dp))
            LinearProgressIndicator(
                progress = { fraction },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(6.dp),
                color = barColor,
                trackColor = MaterialTheme.colorScheme.outlineVariant
            )
            if (ctx.description.isNotBlank()) {
                Spacer(Modifier.height(4.dp))
                Text(
                    text = ctx.description,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

@Composable
private fun PatternSummary(topEntry: Map.Entry<IncidentContext, Int>, total: Int) {
    val topPercent = topEntry.value * 100 / total.coerceAtLeast(1)

    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.primaryContainer
        )
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = "Key pattern",
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.7f)
            )
            Spacer(Modifier.height(4.dp))
            Text(
                text = "${topEntry.key.label} is the most frequent context",
                style = MaterialTheme.typography.titleSmall,
                color = MaterialTheme.colorScheme.onPrimaryContainer
            )
            Spacer(Modifier.height(4.dp))
            Text(
                text = "${topEntry.value} of $total incidents ($topPercent%) involve ${topEntry.key.description.lowercase()}.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.85f)
            )
        }
    }
}

@Composable
private fun EmptyInsights(modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .fillMaxSize()
            .padding(40.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(
                text = "No data yet",
                style = MaterialTheme.typography.headlineSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
            Spacer(Modifier.height(8.dp))
            Text(
                text = "Log some incidents and patterns will appear here.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }
    }
}

private fun contextColor(ctx: IncidentContext): Color = when (ctx) {
    IncidentContext.HANDOVER       -> Color(0xFF1565C0)
    IncidentContext.BEFORE_SCHOOL  -> Color(0xFF6A1B9A)
    IncidentContext.LEAVING_MUMS   -> Color(0xFF00838F)
    IncidentContext.LEAVING_DADS   -> Color(0xFF00695C)
    IncidentContext.ROUTINE_CHANGE -> Color(0xFFE65100)
    IncidentContext.AFTER_CONTACT  -> Color(0xFF880E4F)
    IncidentContext.OTHER          -> Color(0xFF37474F)
}

private fun buildInsightsSummary(
    contextCounts: Map<IncidentContext, Int>,
    total: Int,
    thisWeek: Int,
    lastWeek: Int,
    avgSeverity: Float
): String {
    val dateFormat = SimpleDateFormat("dd MMM yyyy", Locale.getDefault())
    return buildString {
        appendLine("BEHAVIOUR PATTERNS SUMMARY")
        appendLine("Generated: ${dateFormat.format(Date())}")
        appendLine()
        appendLine("Overview")
        appendLine("  Total incidents: $total")
        appendLine("  This week: $thisWeek  |  Last week: $lastWeek")
        appendLine("  Average severity: ${"%.1f".format(avgSeverity)} / 5")
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
            appendLine("Most frequent: ${top.key.label} (${top.value * 100 / total}% of incidents)")
        }
    }
}
