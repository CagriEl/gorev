import { useEffect, useRef } from 'react';
import { Alert } from 'react-native';
import * as Notifications from 'expo-notifications';
import { useAuth } from '../auth/AuthContext';
import { navigationRef } from '../navigation/navigationRef';
import { registerDevicePushToken } from './register';
import './setup';

export function PushNotificationManager() {
  const { token, user } = useAuth();
  const registeredFor = useRef<string | null>(null);

  useEffect(() => {
    if (!token || !user) {
      registeredFor.current = null;
      return;
    }

    if (registeredFor.current === token) {
      return;
    }

    registeredFor.current = token;

    void (async () => {
      const result = await registerDevicePushToken(token);

      if (result.permission === 'denied') {
        Alert.alert(
          'Bildirim izni gerekli',
          'Yeni görev atamalarını telefonunuza almak için bildirimlere izin verin. Ayarlar > Bel-Sistem > Bildirimler üzerinden de açabilirsiniz.',
        );
        return;
      }

      if (result.pushToken) {
        return;
      }

      if (result.error) {
        Alert.alert('Push kaydı tamamlanamadı', result.error);
      }
    })();
  }, [token, user]);

  useEffect(() => {
    const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
      const taskId = response.notification.request.content.data?.task_id;
      if (taskId == null || !navigationRef.isReady()) {
        return;
      }

      const rootRoute = navigationRef.getRootState()?.routes.at(-1)?.name;

      if (rootRoute === 'ManagerApp') {
        navigationRef.navigate('ManagerApp', {
          screen: 'TasksTab',
          params: {
            screen: 'TaskDetail',
            params: { taskId: Number(taskId) },
          },
        });
        return;
      }

      navigationRef.navigate('StaffApp', {
        screen: 'TaskDetail',
        params: { taskId: Number(taskId) },
      });
    });

    return () => subscription.remove();
  }, []);

  return null;
}
