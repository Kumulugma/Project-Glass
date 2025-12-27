<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Powiadomienia Push';
?>

<div class="push-subscribe-page">
    
    <div class="container" style="max-width: 600px; margin-top: 3rem;">
        
        <div class="text-center mb-4">
            <h1><i class="fas fa-bell me-2"></i> Powiadomienia Push</h1>
            <p class="text-muted">Otrzymuj powiadomienia bezpośrednio w przeglądarce</p>
        </div>

        <!-- Status subskrypcji -->
        <div class="card mb-3">
            <div class="card-body">
                <div id="subscription-status" class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Sprawdzanie...</span>
                    </div>
                    <p class="mt-2 text-muted">Sprawdzanie statusu...</p>
                </div>
            </div>
        </div>

        <!-- Identyfikator użytkownika (dla niezalogowanych) -->
        <?php if (Yii::$app->user->isGuest): ?>
        <div class="card mb-3" id="user-identifier-card" style="display: none;">
            <div class="card-body">
                <label class="form-label">Twój email (opcjonalnie)</label>
                <input type="email" id="user-email" class="form-control" placeholder="twoj@email.pl">
                <small class="form-text text-muted">
                    Podaj email żeby otrzymywać powiadomienia tylko dla Twoich tasków. Jeśli zostawisz puste, będziesz otrzymywać wszystkie powiadomienia push.
                </small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Akcje -->
        <div id="actions-container" class="d-none">
            <button id="subscribe-btn" class="btn btn-success btn-lg w-100 mb-2 d-none">
                <i class="fas fa-bell me-2"></i> Włącz powiadomienia
            </button>
            
            <button id="unsubscribe-btn" class="btn btn-danger btn-lg w-100 mb-2 d-none">
                <i class="fas fa-bell-slash me-2"></i> Wyłącz powiadomienia
            </button>
            
            <button id="test-btn" class="btn btn-outline-primary btn-lg w-100 d-none">
                <i class="fas fa-vial me-2"></i> Wyślij testowe powiadomienie
            </button>
        </div>

        <!-- Info -->
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i> Jak to działa?</h6>
                <ul class="small mb-0">
                    <li class="mb-2">Powiadomienia push działają nawet gdy strona jest zamknięta</li>
                    <li class="mb-2">Obsługiwane przeglądarki: Chrome, Firefox, Edge, Safari (iOS 16.4+)</li>
                    <li class="mb-2">Możesz wyłączyć powiadomienia w dowolnym momencie</li>
                    <li>Twoja subskrypcja jest anonimowa - nie przechowujemy danych osobowych</li>
                </ul>
            </div>
        </div>

        <!-- Powrót (jeśli zalogowany) -->
        <?php if (!Yii::$app->user->isGuest): ?>
        <div class="text-center mt-3">
            <?= Html::a('<i class="fas fa-arrow-left me-2"></i> Powrót do aplikacji', ['/dashboard/index'], ['class' => 'btn btn-link']) ?>
        </div>
        <?php endif; ?>

    </div>

</div>

<?php
$publicKeyUrl = Url::to(['/push/public-key'], true);
$subscribeUrl = Url::to(['/push/subscribe'], true);
$unsubscribeUrl = Url::to(['/push/unsubscribe'], true);
$testUrl = Url::to(['/push/test'], true);

$this->registerJs(<<<JS
(function() {
    const statusDiv = document.getElementById('subscription-status');
    const actionsDiv = document.getElementById('actions-container');
    const subscribeBtn = document.getElementById('subscribe-btn');
    const unsubscribeBtn = document.getElementById('unsubscribe-btn');
    const testBtn = document.getElementById('test-btn');
    
    // Sprawdź czy przeglądarka wspiera Push API
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        showStatus('❌ Twoja przeglądarka nie obsługuje powiadomień push', 'danger');
        return;
    }
    
    // Sprawdź status subskrypcji
    async function checkStatus() {
        try {
            // Najpierw sprawdź wsparcie
            if (!('serviceWorker' in navigator)) {
                showStatus('❌ Twoja przeglądarka nie obsługuje Service Workers', 'danger');
                return;
            }
            
            if (!('PushManager' in window)) {
                showStatus('❌ Twoja przeglądarka nie obsługuje Push Notifications', 'danger');
                return;
            }
            
            // Zarejestruj service worker z lepszą obsługą błędów
            let registration;
            try {
                registration = await navigator.serviceWorker.register('/service-worker.js', {
                    scope: '/'
                });
                console.log('Service Worker registered:', registration);
            } catch (swError) {
                console.error('Service Worker error:', swError);
                showStatus('⚠️ Błąd Service Worker: ' + swError.message + '. Sprawdź czy plik /service-worker.js istnieje.', 'warning');
                return;
            }
            
            // Poczekaj aż SW będzie gotowy
            await navigator.serviceWorker.ready;
            
            const subscription = await registration.pushManager.getSubscription();
            
            if (subscription) {
                showStatus('✅ Powiadomienia są włączone', 'success');
                showActions(true);
            } else {
                showStatus('🔕 Powiadomienia są wyłączone', 'warning');
                showActions(false);
                
                // Pokaż pole email dla niezalogowanych
                const userCard = document.getElementById('user-identifier-card');
                if (userCard) {
                    userCard.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Check status error:', error);
            showStatus('❌ Błąd sprawdzania statusu: ' + error.message, 'danger');
            showActions(false);
        }
    }
    
    // Pokaż status
    function showStatus(message, type) {
        statusDiv.innerHTML = '<div class="alert alert-' + type + ' mb-0">' + message + '</div>';
    }
    
    // Pokaż przyciski akcji
    function showActions(isSubscribed) {
        actionsDiv.classList.remove('d-none');
        
        if (isSubscribed) {
            subscribeBtn.classList.add('d-none');
            unsubscribeBtn.classList.remove('d-none');
            testBtn.classList.remove('d-none');
        } else {
            subscribeBtn.classList.remove('d-none');
            unsubscribeBtn.classList.add('d-none');
            testBtn.classList.add('d-none');
        }
    }
    
    // Subskrybuj
    subscribeBtn.addEventListener('click', async function() {
        try {
            subscribeBtn.disabled = true;
            subscribeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Włączanie...';
            
            console.log('Step 1: Requesting permission...');
            
            // Poproś o uprawnienia
            const permission = await Notification.requestPermission();
            
            console.log('Step 2: Permission result:', permission);
            
            if (permission !== 'granted') {
                showStatus('❌ Odmówiono dostępu do powiadomień', 'danger');
                subscribeBtn.disabled = false;
                subscribeBtn.innerHTML = '<i class="fas fa-bell me-2"></i> Włącz powiadomienia';
                return;
            }
            
            console.log('Step 3: Fetching VAPID key...');
            
            // Pobierz VAPID key
            const keyResponse = await fetch('$publicKeyUrl');
            const keyData = await keyResponse.json();
            
            console.log('Step 4: VAPID key received:', keyData);
            
            if (!keyData.publicKey) {
                throw new Error('Brak klucza VAPID');
            }
            
            console.log('Step 5: Converting VAPID key...');
            
            // Konwertuj klucz
            const applicationServerKey = urlBase64ToUint8Array(keyData.publicKey);
            
            console.log('Step 6: Subscribing to push...');
            
            // Subskrybuj
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });
            
            console.log('Step 7: Subscription created:', subscription);
            console.log('Step 8: Subscription JSON:', subscription.toJSON());
            
            // Dodaj email użytkownika (jeśli podany)
            const subscriptionData = subscription.toJSON();
            const userEmailInput = document.getElementById('user-email');
            if (userEmailInput && userEmailInput.value) {
                subscriptionData.user_email = userEmailInput.value;
            }
            
            console.log('Step 8b: Subscription data with email:', subscriptionData);
            
            // Wyślij subskrypcję do serwera
            const response = await fetch('$subscribeUrl', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(subscriptionData)
            });
            
            console.log('Step 9: Server response status:', response.status);
            
            // Sprawdź co zwrócił serwer
            const responseText = await response.text();
            console.log('Step 9b: Server response text:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                throw new Error('Serwer zwrócił nieprawidłową odpowiedź: ' + responseText.substring(0, 200));
            }
            
            console.log('Step 10: Server response data:', data);
            
            if (data.success) {
                showStatus('✅ Powiadomienia włączone pomyślnie!', 'success');
                showActions(true);
            } else {
                throw new Error(data.error || 'Błąd subskrypcji');
            }
            
        } catch (error) {
            console.error('Subscribe error:', error);
            console.error('Error stack:', error.stack);
            showStatus('❌ Błąd: ' + error.message, 'danger');
        } finally {
            subscribeBtn.disabled = false;
            subscribeBtn.innerHTML = '<i class="fas fa-bell me-2"></i> Włącz powiadomienia';
        }
    });
    
    // Odsubskrybuj
    unsubscribeBtn.addEventListener('click', async function() {
        try {
            unsubscribeBtn.disabled = true;
            unsubscribeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Wyłączanie...';
            
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            
            if (subscription) {
                await subscription.unsubscribe();
                
                // Powiadom serwer
                await fetch('$unsubscribeUrl', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ endpoint: subscription.endpoint })
                });
            }
            
            showStatus('🔕 Powiadomienia wyłączone', 'warning');
            showActions(false);
            
        } catch (error) {
            console.error('Unsubscribe error:', error);
            showStatus('❌ Błąd: ' + error.message, 'danger');
        } finally {
            unsubscribeBtn.disabled = false;
            unsubscribeBtn.innerHTML = '<i class="fas fa-bell-slash me-2"></i> Wyłącz powiadomienia';
        }
    });
    
    // Test
    testBtn.addEventListener('click', async function() {
        try {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Wysyłanie...';
            
            // Pobierz endpoint bieżącej subskrypcji
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            
            if (!subscription) {
                showStatus('❌ Brak aktywnej subskrypcji. Najpierw włącz powiadomienia.', 'danger');
                return;
            }
            
            const response = await fetch('$testUrl', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    endpoint: subscription.endpoint // Wyślij tylko do tego urządzenia
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showStatus('✅ Testowe powiadomienie wysłane! Sprawdź swoją przeglądarkę.', 'success');
            } else {
                throw new Error(data.error || 'Błąd wysyłania');
            }
            
        } catch (error) {
            console.error('Test error:', error);
            showStatus('❌ Błąd: ' + error.message, 'danger');
        } finally {
            testBtn.disabled = false;
            testBtn.innerHTML = '<i class="fas fa-vial me-2"></i> Wyślij testowe powiadomienie';
        }
    });
    
    // Helper: konwersja VAPID key
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    
    // Uruchom sprawdzanie
    checkStatus();
})();
JS
);
?>