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
    object Add : Screen("add?id={id}") {
        fun forNew() = "add"
        fun forEdit(id: Int) = "add?id=$id"
    }
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
                onAddClick = { navController.navigate(Screen.Add.forNew()) },
                onEditClick = { id -> navController.navigate(Screen.Add.forEdit(id)) }
            )
        }
        composable(
            route = Screen.Add.route,
            arguments = listOf(
                navArgument("id") {
                    type = NavType.IntType
                    defaultValue = -1
                }
            )
        ) { backStack ->
            val editId = backStack.arguments?.getInt("id")?.takeIf { it != -1 }
            AddIncidentScreen(
                viewModel = viewModel,
                editIncidentId = editId,
                onBack = { navController.popBackStack() }
            )
        }
        composable(Screen.Insights.route) {
            InsightsScreen(viewModel = viewModel)
        }
    }
}
