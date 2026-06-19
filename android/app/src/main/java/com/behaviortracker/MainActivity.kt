package com.behaviortracker

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Insights
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.Insights
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavDestination.Companion.hierarchy
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.behaviortracker.ui.navigation.NavGraph
import com.behaviortracker.ui.navigation.Screen
import com.behaviortracker.ui.theme.BehaviourTrackerTheme
import com.behaviortracker.ui.viewmodel.BehaviorViewModel
import com.behaviortracker.ui.viewmodel.BehaviorViewModelFactory

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            BehaviourTrackerTheme {
                AppRoot()
            }
        }
    }
}

private data class NavItem(
    val screen: Screen,
    val label: String,
    val selectedIcon: androidx.compose.ui.graphics.vector.ImageVector,
    val unselectedIcon: androidx.compose.ui.graphics.vector.ImageVector
)

@Composable
fun AppRoot() {
    val navController = rememberNavController()
    val app = androidx.compose.ui.platform.LocalContext.current.applicationContext as BehaviourTrackerApp
    val viewModel: BehaviorViewModel = viewModel(
        factory = BehaviorViewModelFactory(app.database.behaviorDao())
    )

    val navItems = listOf(
        NavItem(Screen.List, "Log", Icons.Filled.Home, Icons.Outlined.Home),
        NavItem(Screen.Insights, "Insights", Icons.Filled.Insights, Icons.Outlined.Insights)
    )

    Scaffold(
        modifier = Modifier.fillMaxSize(),
        bottomBar = {
            val navBackStackEntry by navController.currentBackStackEntryAsState()
            val currentDestination = navBackStackEntry?.destination
            val showBottomBar = navItems.any { item ->
                currentDestination?.hierarchy?.any { it.route == item.screen.route } == true
            }
            if (showBottomBar) {
                NavigationBar {
                    navItems.forEach { item ->
                        val isSelected = currentDestination?.hierarchy
                            ?.any { it.route == item.screen.route } == true
                        NavigationBarItem(
                            selected = isSelected,
                            onClick = {
                                navController.navigate(item.screen.route) {
                                    popUpTo(navController.graph.findStartDestination().id) {
                                        saveState = true
                                    }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            },
                            icon = {
                                Icon(
                                    imageVector = if (isSelected) item.selectedIcon else item.unselectedIcon,
                                    contentDescription = item.label
                                )
                            },
                            label = { Text(item.label) }
                        )
                    }
                }
            }
        }
    ) { innerPadding ->
        NavGraph(
            navController = navController,
            viewModel = viewModel,
            modifier = Modifier.padding(innerPadding)
        )
    }
}
