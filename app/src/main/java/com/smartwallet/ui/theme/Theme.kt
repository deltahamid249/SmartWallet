package com.smartwallet.ui.theme

import android.app.Activity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

private val LightColorScheme = lightColorScheme(
    primary = NavyPrimary,
    onPrimary = SurfaceCard,
    primaryContainer = NavySurface,
    onPrimaryContainer = SurfaceCard,
    secondary = EmeraldGreen,
    onSecondary = SurfaceCard,
    secondaryContainer = EmeraldContainer,
    onSecondaryContainer = EmeraldDark,
    background = SlateBackground,
    onBackground = TextPrimary,
    surface = SurfaceCard,
    onSurface = TextPrimary,
    surfaceVariant = SlateBackground,
    onSurfaceVariant = TextSecondary,
    outline = BorderLight
)

private val DarkColorScheme = darkColorScheme(
    primary = NavyLight,
    onPrimary = SurfaceCard,
    primaryContainer = NavyDark,
    onPrimaryContainer = SurfaceCard,
    secondary = EmeraldLight,
    onSecondary = NavyDark,
    background = NavyDark,
    onBackground = SurfaceCard,
    surface = NavySurface,
    onSurface = SurfaceCard,
    outline = NavyLight
)

@Composable
fun SmartWalletTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colorScheme = if (darkTheme) DarkColorScheme else LightColorScheme
    val view = LocalView.current
    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = NavyPrimary.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = false
        }
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
