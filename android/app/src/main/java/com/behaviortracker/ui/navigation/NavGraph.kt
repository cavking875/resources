package com.behaviortracker.ui.navigation

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.behaviortracker.ui.screens.AddIncidentScreen
import com.behaviortracker.ui.screens.IncidentListScreen
import com.behaviortracker.ui.screens.InsightsScreen
import com.behaviortracker.ui.viewmodel.BehaviorViewModel

sealed class Screen(val route: String) {
    object List : Screen("list")
    object Add : Screen("add")
    object Insights : Screen("insights")
}

@Composable
fun NavGraph(
    navController: NavHostController,
    viewModel: BehaviorViewModel,
    modifier: Modifier = Modifier
) {
    NavHost(
        navController = navController,
        startDestination = Screen.List.route,
        modifier = modifier
    ) {
        composable(Screen.List.route) {
            IncidentListScreen(
                viewModel = viewModel,
                onAddClick = { navController.navigate(Screen.Add.route) }
            )
        }
        composable(Screen.Add.route) {
            AddIncidentScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable(Screen.Insights.route) {
            InsightsScreen(viewModel = viewModel)
        }
    }
}
