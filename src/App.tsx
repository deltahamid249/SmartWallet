import React, { useState } from 'react';
import { WalletProvider, useWallet } from './context/WalletContext';
import { ScreenType } from './types';
import { Header } from './components/Header';
import { Navigation } from './components/Navigation';
import { UserSwitcherModal } from './components/UserSwitcherModal';

import { HomeScreen } from './screens/HomeScreen';
import { TransferScreen } from './screens/TransferScreen';
import { DepositScreen } from './screens/DepositScreen';
import { WithdrawScreen } from './screens/WithdrawScreen';
import { PaymentsScreen } from './screens/PaymentsScreen';
import { ServicesScreen } from './screens/ServicesScreen';
import { TransactionsScreen } from './screens/TransactionsScreen';
import { NotificationsScreen } from './screens/NotificationsScreen';
import { ProfileScreen } from './screens/ProfileScreen';
import { AdminScreen } from './screens/AdminScreen';
import { SettingsScreen } from './screens/SettingsScreen';
import { HelpScreen } from './screens/HelpScreen';

const WalletApp: React.FC = () => {
  const [currentScreen, setCurrentScreen] = useState<ScreenType>('home');
  const [screenHistory, setScreenHistory] = useState<ScreenType[]>(['home']);
  const [isUserSwitcherOpen, setIsUserSwitcherOpen] = useState(false);

  const navigateTo = (screen: ScreenType) => {
    if (screen === currentScreen) return;
    setScreenHistory((prev) => [...prev, screen]);
    setCurrentScreen(screen);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleBack = () => {
    if (screenHistory.length > 1) {
      const nextHistory = [...screenHistory];
      nextHistory.pop();
      const prevScreen = nextHistory[nextHistory.length - 1] || 'home';
      setScreenHistory(nextHistory);
      setCurrentScreen(prevScreen);
    } else {
      setCurrentScreen('home');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const renderScreen = () => {
    switch (currentScreen) {
      case 'home':
        return <HomeScreen setScreen={navigateTo} />;
      case 'transfer':
        return <TransferScreen onBack={handleBack} setScreen={navigateTo} />;
      case 'deposit':
        return <DepositScreen onBack={handleBack} setScreen={navigateTo} />;
      case 'withdraw':
        return <WithdrawScreen onBack={handleBack} setScreen={navigateTo} />;
      case 'payments':
        return <PaymentsScreen onBack={handleBack} setScreen={navigateTo} />;
      case 'services':
        return <ServicesScreen onBack={handleBack} setScreen={navigateTo} />;
      case 'transactions':
        return <TransactionsScreen onBack={handleBack} />;
      case 'notifications':
        return <NotificationsScreen onBack={handleBack} />;
      case 'profile':
        return (
          <ProfileScreen
            onBack={handleBack}
            setScreen={navigateTo}
            onOpenUserSwitcher={() => setIsUserSwitcherOpen(true)}
          />
        );
      case 'admin':
        return <AdminScreen onBack={handleBack} setScreen={navigateTo} />;
      case 'settings':
        return <SettingsScreen onBack={handleBack} setScreen={navigateTo} />;
      case 'help':
        return <HelpScreen onBack={handleBack} setScreen={navigateTo} />;
      default:
        return <HomeScreen setScreen={navigateTo} />;
    }
  };

  return (
    <div className="min-h-screen bg-[#F4F6F8] text-[#111827] flex flex-col font-['Cairo',sans-serif]">
      {/* Top Header */}
      <Header
        currentScreen={currentScreen}
        setScreen={navigateTo}
        onOpenUserSwitcher={() => setIsUserSwitcherOpen(true)}
      />

      {/* Desktop/Mobile Navigation */}
      <Navigation currentScreen={currentScreen} setScreen={navigateTo} />

      {/* Main Content Area */}
      <main className="flex-1 w-full max-w-4xl mx-auto px-3 sm:px-6 pt-5 pb-20 md:pb-10">
        {renderScreen()}
      </main>

      {/* Fast Account Switcher & Registration Modal */}
      <UserSwitcherModal
        isOpen={isUserSwitcherOpen}
        onClose={() => setIsUserSwitcherOpen(false)}
      />
    </div>
  );
};

export function App() {
  return (
    <WalletProvider>
      <WalletApp />
    </WalletProvider>
  );
}

export default App;
