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
                BehaviourTrackerApp()
            }
        }
    }
}

@Composable
fun BehaviourTrackerApp() {
    val navController = rememberNavController()
    val app = androidx.compose.ui.platform.LocalContext.current.applicationContext as BehaviourTrackerApp
    val viewModel: BehaviorViewModel = viewModel(
        factory = BehaviorViewModelFactory(app.database.behaviorDao())
    )

    val bottomNavItems = listOf(
        Screen.List to Pair("Log", Icons.Default.Home),
        Screen.Insights to Pair("Insights", Icons.Default.Insights)
    )

    Scaffold(
        modifier = Modifier.fillMaxSize(),
        bottomBar = {
            val navBackStackEntry by navController.currentBackStackEntryAsState()
            val currentDestination = navBackStackEntry?.destination
            val showBottomBar = bottomNavItems.any { (screen, _) ->
                currentDestination?.hierarchy?.any { it.route == screen.route } == true
            }
            if (showBottomBar) {
                NavigationBar {
                    bottomNavItems.forEach { (screen, meta) ->
                        val (label, icon) = meta
                        NavigationBarItem(
                            icon = { Icon(icon, contentDescription = label) },
                            label = { Text(label) },
                            selected = currentDestination?.hierarchy?.any { it.route == screen.route } == true,
                            onClick = {
                                navController.navigate(screen.route) {
                                    popUpTo(navController.graph.findStartDestination().id) {
                                        saveState = true
                                    }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            }
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
