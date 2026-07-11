import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import { registerPushToken, unregisterPushToken } from '../api/client';
import { ensureAndroidNotificationChannel } from './setup';

let cachedPushToken: string | null = null;

export type PushRegistrationResult = {
  permission: Notifications.PermissionStatus;
  pushToken: string | null;
  error?: string;
};

function resolveProjectId(): string | undefined {
  return (
    Constants.expoConfig?.extra?.eas?.projectId ??
    Constants.easConfig?.projectId ??
    undefined
  );
}

export async function requestNotificationPermission(): Promise<Notifications.PermissionStatus> {
  await ensureAndroidNotificationChannel();

  const { status: existing } = await Notifications.getPermissionsAsync();
  if (existing === 'granted') {
    return existing;
  }

  const { status } = await Notifications.requestPermissionsAsync({
    ios: {
      allowAlert: true,
      allowBadge: true,
      allowSound: true,
    },
  });

  return status;
}

export async function registerDevicePushToken(apiToken: string): Promise<PushRegistrationResult> {
  const permission = await requestNotificationPermission();
  if (permission !== 'granted') {
    return { permission, pushToken: null };
  }

  try {
    const projectId = resolveProjectId();
    const pushTokenResponse = projectId
      ? await Notifications.getExpoPushTokenAsync({ projectId })
      : await Notifications.getExpoPushTokenAsync();

    const pushToken = pushTokenResponse.data;
    cachedPushToken = pushToken;

    await registerPushToken(apiToken, {
      token: pushToken,
      platform: Platform.OS === 'ios' ? 'ios' : 'android',
      device_name: Device.isDevice
        ? (Device.modelName ?? 'bel-sistem-mobile')
        : `simulator-${Platform.OS}`,
    });

    return { permission, pushToken };
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Push belirteci alınamadı';

    return {
      permission,
      pushToken: null,
      error: Device.isDevice
        ? message
        : `${message} (Simülatörde uzak push sınırlı olabilir; fiziksel cihaz önerilir.)`,
    };
  }
}

export async function unregisterDevicePushToken(apiToken: string): Promise<void> {
  if (!cachedPushToken) {
    return;
  }

  try {
    await unregisterPushToken(apiToken, cachedPushToken);
  } catch {
    // ignore
  } finally {
    cachedPushToken = null;
  }
}

export function getCachedPushToken(): string | null {
  return cachedPushToken;
}
