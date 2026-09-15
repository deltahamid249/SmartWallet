package com.smartwallet.ui

import androidx.compose.runtime.Composable
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.smartwallet.ui.screens.*

@Composable
fun SmartWalletApp(viewModel: SmartWalletViewModel) {
    val navController = rememberNavController()

    NavHost(
        navController = navController,
        startDestination = "home"
    ) {
        composable("home") {
            HomeScreen(
                viewModel = viewModel,
                onNavigate = { route -> navController.navigate(route) }
            )
        }
        composable("transfer") {
            TransferScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("deposit") {
            DepositScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("withdraw") {
            WithdrawScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("payments") {
            PaymentsScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("services") {
            ServicesScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("transactions") {
            TransactionsScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("notifications") {
            NotificationsScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("profile") {
            ProfileScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("admin") {
            AdminScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() }
            )
        }
        composable("settings") {
            SettingsScreen(
                onBack = { navController.popBackStack() }
            )
        }
        composable("help") {
            HelpScreen(
                onBack = { navController.popBackStack() }
            )
        }
    }
}
