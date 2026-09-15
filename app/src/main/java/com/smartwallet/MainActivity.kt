package com.smartwallet

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.lifecycleScope
import com.smartwallet.data.SmartWalletDatabase
import com.smartwallet.data.SmartWalletRepository
import com.smartwallet.ui.SmartWalletApp
import com.smartwallet.ui.SmartWalletViewModel
import com.smartwallet.ui.theme.NavyPrimary
import com.smartwallet.ui.theme.SmartWalletTheme

class MainActivity : ComponentActivity() {

    private val viewModel: SmartWalletViewModel by viewModels {
        object : ViewModelProvider.Factory {
            @Suppress("UNCHECKED_CAST")
            override fun <T : ViewModel> create(modelClass: Class<T>): T {
                val database = SmartWalletDatabase.getDatabase(applicationContext, lifecycleScope)
                val repository = SmartWalletRepository(database)
                return SmartWalletViewModel(repository) as T
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        setContent {
            SmartWalletTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = NavyPrimary
                ) {
                    SmartWalletApp(viewModel = viewModel)
                }
            }
        }
    }
}
