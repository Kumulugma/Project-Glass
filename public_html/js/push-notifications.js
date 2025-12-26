// Push Notifications Handler
(function() {
  'use strict';

  // Sprawdź czy przeglądarka wspiera Service Workers i Push API
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    console.warn('Push notifications not supported');
    return;
  }

  // Rejestracja service workera
  async function registerServiceWorker() {
    try {
      const registration = await navigator.serviceWorker.register('/js/service-worker.js');
      console.log('Service Worker registered:', registration);
      return registration;
    } catch (error) {
      console.error('Service Worker registration failed:', error);
      throw error;
    }
  }

  // Konwersja VAPID key
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

  // Subskrybuj push notifications
  async function subscribeToPush(registration) {
    try {
      // Pobierz VAPID public key z serwera
      const response = await fetch('/push/public-key');
      const data = await response.json();
      
      if (!data.publicKey) {
        throw new Error('Failed to get VAPID public key');
      }

      const applicationServerKey = urlBase64ToUint8Array(data.publicKey);

      // Subskrybuj
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey
      });

      console.log('Push subscription:', subscription);

      // Wyślij subskrypcję do serwera
      await fetch('/push/subscribe', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(subscription.toJSON())
      });

      console.log('Subscription sent to server');
      return subscription;

    } catch (error) {
      console.error('Failed to subscribe to push:', error);
      throw error;
    }
  }

  // Odsubskrybuj
  async function unsubscribeFromPush(registration) {
    try {
      const subscription = await registration.pushManager.getSubscription();
      
      if (subscription) {
        await subscription.unsubscribe();
        
        // Powiadom serwer
        await fetch('/push/unsubscribe', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ endpoint: subscription.endpoint })
        });
        
        console.log('Unsubscribed from push');
      }
    } catch (error) {
      console.error('Failed to unsubscribe:', error);
    }
  }

  // Sprawdź status subskrypcji
  async function checkSubscriptionStatus(registration) {
    const subscription = await registration.pushManager.getSubscription();
    return subscription !== null;
  }

  // Główna funkcja inicjalizacji
  async function initPushNotifications() {
    try {
      const registration = await registerServiceWorker();
      
      // Sprawdź czy już zasubskrybowany
      const isSubscribed = await checkSubscriptionStatus(registration);
      
      if (!isSubscribed) {
        // Poproś o uprawnienia
        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
          await subscribeToPush(registration);
          console.log('Push notifications enabled');
          return true;
        } else {
          console.log('Push notification permission denied');
          return false;
        }
      } else {
        console.log('Already subscribed to push');
        return true;
      }
      
    } catch (error) {
      console.error('Push initialization error:', error);
      return false;
    }
  }

  // Udostępnij globalnie
  window.TaskReminder = window.TaskReminder || {};
  window.TaskReminder.Push = {
    init: initPushNotifications,
    subscribe: async () => {
      const registration = await navigator.serviceWorker.ready;
      return subscribeToPush(registration);
    },
    unsubscribe: async () => {
      const registration = await navigator.serviceWorker.ready;
      return unsubscribeFromPush(registration);
    },
    checkStatus: async () => {
      const registration = await navigator.serviceWorker.ready;
      return checkSubscriptionStatus(registration);
    }
  };

  // Auto-init gdy dokument gotowy (jeśli użytkownik już wyraził zgodę)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      // Sprawdź czy użytkownik już wyraził zgodę
      if (Notification.permission === 'granted') {
        initPushNotifications();
      }
    });
  } else {
    if (Notification.permission === 'granted') {
      initPushNotifications();
    }
  }

})();
