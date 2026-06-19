package com.behaviortracker.ui.theme

import android.app.Activity
import android.os.Build
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

private val LightColorScheme = lightColorScheme(
    primary = Indigo40,
    onPrimary = Color.White,
    primaryContainer = Indigo90,
    onPrimaryContainer = Indigo10,
    secondary = Teal40,
    onSecondary = Color.White,
    secondaryContainer = Teal90,
    onSecondaryContainer = Color(0xFF002018),
    tertiary = Amber40,
    onTertiary = Color.White,
    tertiaryContainer = Amber90,
    onTertiaryContainer = Color(0xFF370E00),
    surface = Neutral99,
    onSurface = Neutral10,
    surfaceVariant = NeutralVar90,
    onSurfaceVariant = NeutralVar40,
    outline = NeutralVar80,
    outlineVariant = Color(0xFFCAC4D0),
    background = Neutral99,
    onBackground = Neutral10
)

private val DarkColorScheme = darkColorScheme(
    primary = Indigo80,
    onPrimary = Indigo20,
    primaryContainer = Indigo40,
    onPrimaryContainer = Indigo90,
    secondary = Teal80,
    onSecondary = Color(0xFF003731),
    secondaryContainer = Teal40,
    onSecondaryContainer = Teal90,
    tertiary = Amber80,
    onTertiary = Color(0xFF5C2A00),
    tertiaryContainer = Amber40,
    onTertiaryContainer = Amber90,
    surface = Color(0xFF131318),
    onSurface = Neutral90,
    surfaceVariant = Color(0xFF47464F),
    onSurfaceVariant = NeutralVar80,
    outline = NeutralVar40,
    outlineVariant = Color(0xFF49454F),
    background = Color(0xFF131318),
    onBackground = Neutral90
)

@Composable
fun BehaviourTrackerTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colorScheme = when {
        Build.VERSION.SDK_INT >= Build.VERSION_CODES.S -> {
            val context = LocalContext.current
            if (darkTheme) dynamicDarkColorScheme(context) else dynamicLightColorScheme(context)
        }
        darkTheme -> DarkColorScheme
        else -> LightColorScheme
    }

    val view = LocalView.current
    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = !darkTheme
        }
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
