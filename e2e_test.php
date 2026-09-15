<?php

require __DIR__ . '/config/database.php';

function ok(string $message): void
{
    echo "OK   $message\n";
}

function fail(string $message): void
{
    echo "FAIL $message\n";
    throw new RuntimeException($message);
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fail($message);
    }

    ok($message);
}

function amount(string $value): string
{
    return number_format((float) $value, 2, '.', '');
}

echo "===== SMARTWALLET REAL E2E DATABASE TEST =====\n";
echo "This test uses temporary data and rolls everything back.\n\n";

$pdo->beginTransaction();

try {
    /*
     * ---------------------------------------------------------
     * 1. CREATE TEMPORARY USERS
     * ---------------------------------------------------------
     */

    $suffix = date('YmdHis') . random_int(1000, 9999);

    $passwordHash = password_hash('E2E_TEST_ONLY_123!', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users
            (full_name, phone, email, password_hash, status, role)
        VALUES
            (:name, :phone, :email, :password_hash, 'active', 'user')
    ");

    $stmt->execute([
        ':name' => 'E2E Test User One',
        ':phone' => '090E2E' . $suffix,
        ':email' => 'e2e_user1_' . $suffix . '@test.local',
        ':password_hash' => $passwordHash
    ]);

    $user1 = (int) $pdo->lastInsertId();

    $stmt->execute([
        ':name' => 'E2E Test User Two',
        ':phone' => '091E2E' . $suffix,
        ':email' => 'e2e_user2_' . $suffix . '@test.local',
        ':password_hash' => $passwordHash
    ]);

    $user2 = (int) $pdo->lastInsertId();

    ok("Temporary user 1 created");
    ok("Temporary user 2 created");

    /*
     * ---------------------------------------------------------
     * 2. CREATE WALLETS
     * ---------------------------------------------------------
     */

    $stmt = $pdo->prepare("
        INSERT INTO wallets (user_id, balance, currency)
        VALUES (:user_id, :balance, 'SDG')
    ");

    $stmt->execute([
        ':user_id' => $user1,
        ':balance' => '0.00'
    ]);

    $wallet1 = (int) $pdo->lastInsertId();

    $stmt->execute([
        ':user_id' => $user2,
        ':balance' => '0.00'
    ]);

    $wallet2 = (int) $pdo->lastInsertId();

    ok("Wallet 1 created");
    ok("Wallet 2 created");

    /*
     * ---------------------------------------------------------
     * 3. TEST DEPOSIT REQUEST
     * ---------------------------------------------------------
     */

    $depositRef = 'E2E-DEP-' . $suffix;

    $stmt = $pdo->prepare("
        INSERT INTO deposit_requests
        (
            reference,
            user_id,
            wallet_id,
            amount,
            bank_name,
            sender_name,
            bank_reference,
            note,
            status
        )
        VALUES
        (
            :reference,
            :user_id,
            :wallet_id,
            :amount,
            :bank_name,
            :sender_name,
            :bank_reference,
            :note,
            'pending'
        )
    ");

    $stmt->execute([
        ':reference' => $depositRef,
        ':user_id' => $user1,
        ':wallet_id' => $wallet1,
        ':amount' => '1000.00',
        ':bank_name' => 'E2E TEST BANK',
        ':sender_name' => 'E2E TEST USER',
        ':bank_reference' => 'E2E-BANK-' . $suffix,
        ':note' => 'Temporary automated E2E test'
    ]);

    $depositId = (int) $pdo->lastInsertId();

    $row = $pdo->prepare("
        SELECT status, amount
        FROM deposit_requests
        WHERE id = :id
    ");
    $row->execute([':id' => $depositId]);
    $deposit = $row->fetch(PDO::FETCH_ASSOC);

    assertTrue($deposit['status'] === 'pending', 'Deposit request starts pending');
    assertTrue(amount($deposit['amount']) === '1000.00', 'Deposit amount is 1000.00');

    /*
     * ---------------------------------------------------------
     * 4. APPROVE DEPOSIT
     * ---------------------------------------------------------
     */

    $pdo->exec("
        UPDATE wallets
        SET balance = balance + 1000.00
        WHERE id = {$wallet1}
    ");

    $stmt = $pdo->prepare("
        UPDATE deposit_requests
        SET
            status = 'approved',
            reviewed_by = :reviewed_by,
            reviewed_at = CURRENT_TIMESTAMP,
            review_note = 'E2E approved'
        WHERE id = :id
    ");

    $stmt->execute([
        ':reviewed_by' => $user1,
        ':id' => $depositId
    ]);

    $balance = $pdo->prepare("
        SELECT balance
        FROM wallets
        WHERE id = :id
    ");
    $balance->execute([':id' => $wallet1]);

    assertTrue(
        amount((string) $balance->fetchColumn()) === '1000.00',
        'Wallet 1 receives approved deposit'
    );

    /*
     * ---------------------------------------------------------
     * 5. DEPOSIT TRANSACTION
     * ---------------------------------------------------------
     */

    $stmt = $pdo->prepare("
        INSERT INTO transactions
        (
            wallet_id,
            type,
            amount,
            reference,
            description,
            status
        )
        VALUES
        (
            :wallet_id,
            'deposit',
            :amount,
            :reference,
            :description,
            'completed'
        )
    ");

    $stmt->execute([
        ':wallet_id' => $wallet1,
        ':amount' => '1000.00',
        ':reference' => 'E2E-TXN-DEP-' . $suffix,
        ':description' => 'E2E deposit test'
    ]);

    ok("Deposit transaction recorded");

    /*
     * ---------------------------------------------------------
     * 6. TRANSFER
     * ---------------------------------------------------------
     */

    $transferRef = 'E2E-TRF-' . $suffix;

    $pdo->exec("
        UPDATE wallets
        SET balance = balance - 300.00
        WHERE id = {$wallet1}
          AND balance >= 300.00
    ");

    $affected = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();

    assertTrue((int) $affected === 1, 'Sender has sufficient balance');

    $pdo->exec("
        UPDATE wallets
        SET balance = balance + 300.00
        WHERE id = {$wallet2}
    ");

    $stmt = $pdo->prepare("
        INSERT INTO transfers
        (
            sender_wallet_id,
            receiver_wallet_id,
            amount,
            reference,
            status,
            note
        )
        VALUES
        (
            :sender,
            :receiver,
            :amount,
            :reference,
            'completed',
            'E2E transfer test'
        )
    ");

    $stmt->execute([
        ':sender' => $wallet1,
        ':receiver' => $wallet2,
        ':amount' => '300.00',
        ':reference' => $transferRef
    ]);

    ok("Transfer recorded");

    /*
     * ---------------------------------------------------------
     * 7. TRANSFER TRANSACTIONS
     * ---------------------------------------------------------
     */

    $stmt = $pdo->prepare("
        INSERT INTO transactions
        (
            wallet_id,
            type,
            amount,
            reference,
            description,
            status
        )
        VALUES
        (
            :wallet_id,
            'transfer_out',
            :amount,
            :reference,
            'E2E transfer out',
            'completed'
        )
    ");

    $stmt->execute([
        ':wallet_id' => $wallet1,
        ':amount' => '300.00',
        ':reference' => 'E2E-TXN-OUT-' . $suffix
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO transactions
        (
            wallet_id,
            type,
            amount,
            reference,
            description,
            status
        )
        VALUES
        (
            :wallet_id,
            'transfer_in',
            :amount,
            :reference,
            'E2E transfer in',
            'completed'
        )
    ");

    $stmt->execute([
        ':wallet_id' => $wallet2,
        ':amount' => '300.00',
        ':reference' => 'E2E-TXN-IN-' . $suffix
    ]);

    /*
     * ---------------------------------------------------------
     * 8. PAYMENT
     * ---------------------------------------------------------
     */

    $pdo->exec("
        UPDATE wallets
        SET balance = balance - 100.00
        WHERE id = {$wallet1}
          AND balance >= 100.00
    ");

    $affected = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();

    assertTrue((int) $affected === 1, 'Payment balance check succeeds');

    $stmt = $pdo->prepare("
        INSERT INTO payments
        (
            user_id,
            amount,
            merchant_name,
            reference,
            status
        )
        VALUES
        (
            :user_id,
            :amount,
            :merchant,
            :reference,
            'completed'
        )
    ");

    $stmt->execute([
        ':user_id' => $user1,
        ':amount' => '100.00',
        ':merchant' => 'E2E Test Merchant',
        ':reference' => 'E2E-PAY-' . $suffix
    ]);

    ok("Payment recorded");

    /*
     * ---------------------------------------------------------
     * 9. WITHDRAWAL REQUEST
     * ---------------------------------------------------------
     */

    $stmt = $pdo->prepare("
        INSERT INTO withdrawal_requests
        (
            reference,
            user_id,
            wallet_id,
            amount,
            recipient_name,
            recipient_phone,
            withdrawal_method,
            note,
            status
        )
        VALUES
        (
            :reference,
            :user_id,
            :wallet_id,
            :amount,
            :recipient_name,
            :recipient_phone,
            'manual',
            'E2E withdrawal test',
            'pending'
        )
    ");

    $stmt->execute([
        ':reference' => 'E2E-WDR-' . $suffix,
        ':user_id' => $user1,
        ':wallet_id' => $wallet1,
        ':amount' => '200.00',
        ':recipient_name' => 'E2E Recipient',
        ':recipient_phone' => '0990000000'
    ]);

    $withdrawalId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        SELECT status
        FROM withdrawal_requests
        WHERE id = :id
    ");
    $stmt->execute([':id' => $withdrawalId]);

    assertTrue(
        $stmt->fetchColumn() === 'pending',
        'Withdrawal request starts pending'
    );

    /*
     * ---------------------------------------------------------
     * 10. APPROVE WITHDRAWAL
     * ---------------------------------------------------------
     */

    $pdo->exec("
        UPDATE wallets
        SET balance = balance - 200.00
        WHERE id = {$wallet1}
          AND balance >= 200.00
    ");

    $affected = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();

    assertTrue((int) $affected === 1, 'Withdrawal balance check succeeds');

    $stmt = $pdo->prepare("
        UPDATE withdrawal_requests
        SET
            status = 'approved',
            reviewed_by = :reviewed_by,
            reviewed_at = CURRENT_TIMESTAMP,
            review_note = 'E2E approved'
        WHERE id = :id
    ");

    $stmt->execute([
        ':reviewed_by' => $user1,
        ':id' => $withdrawalId
    ]);

    ok("Withdrawal approved");

    /*
     * ---------------------------------------------------------
     * 11. ALL SIX SERVICES
     * ---------------------------------------------------------
     */

    $services = [
        'mobile_recharge',
        'electricity',
        'internet',
        'bill_payment',
        'education',
        'government'
    ];

    foreach ($services as $index => $service) {

        $reference = 'E2E-SRV-' . strtoupper(substr($service, 0, 3)) . '-' . $suffix . '-' . $index;

        $stmt = $pdo->prepare("
            INSERT INTO service_requests
            (
                user_id,
                service_type,
                provider,
                phone_number,
                account_number,
                amount,
                fee,
                total_amount,
                reference,
                status,
                response_message
            )
            VALUES
            (
                :user_id,
                :service_type,
                'E2E TEST PROVIDER',
                '0990000000',
                'E2E-ACCOUNT',
                :amount,
                :fee,
                :total_amount,
                :reference,
                'pending',
                'E2E service test'
            )
        ");

        $stmt->execute([
            ':user_id' => $user1,
            ':service_type' => $service,
            ':amount' => '50.00',
            ':fee' => '0.00',
            ':total_amount' => '50.00',
            ':reference' => $reference
        ]);

        ok("Service request created: {$service}");
    }

    /*
     * ---------------------------------------------------------
     * 12. ADMIN NOTIFICATION
     * ---------------------------------------------------------
     */

    $stmt = $pdo->prepare("
        INSERT INTO admin_notifications
        (
            title,
            message,
            type,
            target_type,
            target_id
        )
        VALUES
        (
            :title,
            :message,
            'info',
            'user',
            :target_id
        )
    ");

    $stmt->execute([
        ':title' => 'E2E Test Notification',
        ':message' => 'Temporary E2E notification',
        ':target_id' => $user1
    ]);

    ok("Admin notification created");

    /*
     * ---------------------------------------------------------
     * 13. AUDIT LOG
     * ---------------------------------------------------------
     */

    $stmt = $pdo->prepare("
        INSERT INTO admin_audit_logs
        (
            admin_id,
            action,
            target_type,
            target_id,
            description,
            ip_address
        )
        VALUES
        (
            :admin_id,
            'e2e_test',
            'user',
            :target_id,
            'Temporary E2E audit entry',
            '127.0.0.1'
        )
    ");

    $stmt->execute([
        ':admin_id' => $user1,
        ':target_id' => $user1
    ]);

    ok("Admin audit log created");

    /*
     * ---------------------------------------------------------
     * 14. FINAL BALANCE CHECK
     * ---------------------------------------------------------
     *
     * User 1:
     * +1000 deposit
     * -300 transfer
     * -100 payment
     * -200 withdrawal
     * = 400
     *
     * User 2:
     * +300 transfer
     * = 300
     */

    $stmt = $pdo->prepare("
        SELECT balance
        FROM wallets
        WHERE id = :id
    ");

    $stmt->execute([':id' => $wallet1]);
    $final1 = amount((string) $stmt->fetchColumn());

    $stmt->execute([':id' => $wallet2]);
    $final2 = amount((string) $stmt->fetchColumn());

    assertTrue($final1 === '400.00', 'User 1 final balance = 400.00');
    assertTrue($final2 === '300.00', 'User 2 final balance = 300.00');

    /*
     * ---------------------------------------------------------
     * 15. VERIFY SERVICE COUNT
     * ---------------------------------------------------------
     */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM service_requests
        WHERE user_id = :user_id
    ");

    $stmt->execute([':user_id' => $user1]);

    assertTrue(
        (int) $stmt->fetchColumn() === 6,
        'All six service types recorded'
    );

    /*
     * ---------------------------------------------------------
     * 16. VERIFY ROLLBACK
     * ---------------------------------------------------------
     */

    $pdo->rollBack();

    echo "\n===== ROLLBACK CHECK =====\n";

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users
        WHERE email LIKE :email
    ");

    $stmt->execute([
        ':email' => 'e2e_user1_' . $suffix . '@test.local'
    ]);

    assertTrue(
        (int) $stmt->fetchColumn() === 0,
        'Temporary test users removed by rollback'
    );

    echo "\n========================================\n";
    echo "REAL_E2E_DATABASE_TEST_PASSED\n";
    echo "NO_TEST_DATA_LEFT_IN_DATABASE\n";
    echo "========================================\n";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "\n========================================\n";
    echo "REAL_E2E_DATABASE_TEST_FAILED\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "ALL_TEST_DATA_ROLLED_BACK\n";
    echo "========================================\n";

    exit(1);
}
