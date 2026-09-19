package com.example

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Apps
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.outlined.Apps
import androidx.compose.material.icons.outlined.History
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.Person
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.LayoutDirection
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.example.data.local.AppDatabase
import com.example.data.repository.WalletRepository
import com.example.ui.screens.DepositScreen
import com.example.ui.screens.HomeScreen
import com.example.ui.screens.NotificationsScreen
import com.example.ui.screens.PaymentsScreen
import com.example.ui.screens.ProfileScreen
import com.example.ui.screens.ReceiptDialog
import com.example.ui.screens.ServicesScreen
import com.example.ui.screens.TransactionsScreen
import com.example.ui.screens.TransferScreen
import com.example.ui.screens.WithdrawScreen
import com.example.ui.theme.BorderLight
import com.example.ui.theme.EmeraldDark
import com.example.ui.theme.EmeraldGreen
import com.example.ui.theme.EmeraldLight
import com.example.ui.theme.Navy900
import com.example.ui.theme.SmartWalletTheme
import com.example.ui.theme.TextPrimary
import com.example.ui.theme.TextSecondary
import com.example.ui.viewmodel.UiMessage
import com.example.ui.viewmodel.WalletViewModel
import com.example.ui.viewmodel.WalletViewModelFactory
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

sealed class BottomNavItem(
    val route: String,
    val title: String,
    val selectedIcon: ImageVector,
    val unselectedIcon: ImageVector
) {
    data object Home : BottomNavItem("home", "الرئيسية", Icons.Filled.Home, Icons.Outlined.Home)
    data object Services : BottomNavItem("services", "الخدمات", Icons.Filled.Apps, Icons.Outlined.Apps)
    data object Transactions : BottomNavItem("transactions", "المعاملات", Icons.Filled.History, Icons.Outlined.History)
    data object Profile : BottomNavItem("profile", "حسابي", Icons.Filled.Person, Icons.Outlined.Person)
}

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)

        val database = AppDatabase.getDatabase(applicationContext)
        val repository = WalletRepository(database)
        val viewModelFactory = WalletViewModelFactory(repository)

        setContent {
            SmartWalletTheme {
                val viewModel: WalletViewModel = viewModel(factory = viewModelFactory)
                SmartWalletApp(viewModel = viewModel)
            }
        }
    }
}

@Composable
fun SmartWalletApp(viewModel: WalletViewModel) {
    val navController = rememberNavController()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route ?: "home"

    val snackbarHostState = remember { SnackbarHostState() }
    val coroutineScope = rememberCoroutineScope()

    var receiptData by remember { mutableStateOf<Triple<String, String, String>?>(null) }

    LaunchedEffect(Unit) {
        viewModel.uiMessage.collectLatest { message ->
            when (message) {
                is UiMessage.Success -> {
                    coroutineScope.launch {
                        snackbarHostState.showSnackbar(message.message)
                    }
                }
                is UiMessage.Error -> {
                    coroutineScope.launch {
                        snackbarHostState.showSnackbar(message.message)
                    }
                }
                is UiMessage.ServiceReceipt -> {
                    receiptData = Triple(message.title, message.reference, message.note)
                }
            }
        }
    }

    val bottomNavItems = listOf(
        BottomNavItem.Home,
        BottomNavItem.Services,
        BottomNavItem.Transactions,
        BottomNavItem.Profile
    )

    val showBottomBar = currentRoute in bottomNavItems.map { it.route }

    // Use Right-to-Left (RTL) Layout Direction for Arabic language fidelity
    CompositionLocalProvider(LocalLayoutDirection provides LayoutDirection.Rtl) {
        Scaffold(
            snackbarHost = { SnackbarHost(snackbarHostState) },
            bottomBar = {
                if (showBottomBar) {
                    NavigationBar(
                        containerColor = Color.White,
                        tonalElevation = 8.dp,
                        modifier = Modifier
                            .background(Color.White)
                            .testTag("bottom_nav_bar")
                    ) {
                        bottomNavItems.forEach { item ->
                            val isSelected = currentRoute == item.route
                            NavigationBarItem(
                                selected = isSelected,
                                onClick = {
                                    if (currentRoute != item.route) {
                                        navController.navigate(item.route) {
                                            popUpTo(navController.graph.findStartDestination().id) {
                                                saveState = true
                                            }
                                            launchSingleTop = true
                                            restoreState = true
                                        }
                                    }
                                },
                                icon = {
                                    Icon(
                                        imageVector = if (isSelected) item.selectedIcon else item.unselectedIcon,
                                        contentDescription = item.title,
                                        modifier = Modifier.size(24.dp)
                                    )
                                },
                                label = {
                                    Text(
                                        text = item.title,
                                        fontSize = 11.sp,
                                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium
                                    )
                                },
                                colors = NavigationBarItemDefaults.colors(
                                    selectedIconColor = Navy900,
                                    selectedTextColor = Navy900,
                                    indicatorColor = EmeraldLight,
                                    unselectedIconColor = TextSecondary,
                                    unselectedTextColor = TextSecondary
                                )
                            )
                        }
                    }
                }
            },
            containerColor = MaterialTheme.colorScheme.background
        ) { paddingValues ->
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
            ) {
                NavHost(
                    navController = navController,
                    startDestination = "home",
                    modifier = Modifier.fillMaxSize()
                ) {
                    composable("home") {
                        HomeScreen(
                            viewModel = viewModel,
                            onNavigateToTransfer = { navController.navigate("transfer") },
                            onNavigateToDeposit = { navController.navigate("deposit") },
                            onNavigateToWithdraw = { navController.navigate("withdraw") },
                            onNavigateToPayments = { navController.navigate("payments") },
                            onNavigateToServices = { navController.navigate("services") },
                            onNavigateToTransactions = { navController.navigate("transactions") },
                            onNavigateToNotifications = { navController.navigate("notifications") },
                            onNavigateToProfile = { navController.navigate("profile") }
                        )
                    }

                    composable("services") {
                        ServicesScreen(
                            viewModel = viewModel,
                            onNavigateBack = { navController.navigateUp() }
                        )
                    }

                    composable("transactions") {
                        TransactionsScreen(
                            viewModel = viewModel,
                            onNavigateBack = { navController.navigateUp() }
                        )
                    }

                    composable("profile") {
                        ProfileScreen(
                            viewModel = viewModel,
                            onNavigateBack = { navController.navigateUp() }
                        )
                    }

                    composable("transfer") {
                        TransferScreen(
                            viewModel = viewModel,
                            onNavigateBack = { navController.navigateUp() }
                        )
                    }

                    composable("deposit") {
                        DepositScreen(
                            viewModel = viewModel,
                            onNavigateBack = { navController.navigateUp() }
                        )
                    }

                    composable("withdraw") {
                        WithdrawScreen(
                            viewModel = viewModel,
                            onNavigateBack = { navController.navigateUp() }
                        )
                    }

                    composable("payments") {
                        PaymentsScreen(
                            viewModel = viewModel,
                            onNavigateBack = { navController.navigateUp() }
                        )
                    }

                    composable("notifications") {
                        NotificationsScreen(
                            viewModel = viewModel,
                            onNavigateBack = { navController.navigateUp() }
                        )
                    }
                }

                if (receiptData != null) {
                    val (title, ref, note) = receiptData!!
                    ReceiptDialog(
                        title = title,
                        reference = ref,
                        note = note,
                        onDismiss = { receiptData = null }
                    )
                }
            }
        }
    }
}
