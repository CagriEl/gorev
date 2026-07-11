import * as ImagePicker from 'expo-image-picker';
import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useFocusEffect } from '@react-navigation/native';
import { fetchTask, patchTask, uploadTaskPhoto } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import type { TaskStackParamList } from '../navigation/types';
import { PriorityBadge, StatusBadge, colors, sharedStyles } from '../theme';
import { STATUS_LABELS, isReportRole, type Task } from '../types';

type Props = NativeStackScreenProps<TaskStackParamList, 'TaskDetail'>;

export function TaskDetailScreen({ route }: Props) {
  const { taskId } = route.params;
  const { token, user } = useAuth();
  const readOnly = user ? isReportRole(user.role) : false;
  const [task, setTask] = useState<Task | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [solutionNote, setSolutionNote] = useState('Sahada tamamlandı.');

  const load = useCallback(async () => {
    if (!token) return;
    const t = await fetchTask(token, taskId);
    setTask(t);
    if (t.solution_note) setSolutionNote(t.solution_note);
  }, [token, taskId]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      load()
        .catch(() => setTask(null))
        .finally(() => setLoading(false));
    }, [load]),
  );

  async function pickPhoto(): Promise<string | null> {
    const perm = await ImagePicker.requestCameraPermissionsAsync();
    if (!perm.granted) {
      Alert.alert('Kamera izni gerekli');
      return null;
    }
    const result = await ImagePicker.launchCameraAsync({
      quality: 0.7,
      allowsEditing: false,
    });
    if (result.canceled || !result.assets[0]) return null;
    return result.assets[0].uri;
  }

  async function markOnSite() {
    if (!token || !task) return;
    setBusy(true);
    try {
      const uri = await pickPhoto();
      if (!uri) return;
      const path = await uploadTaskPhoto(token, uri, 'arrival');
      const arrival = [...(task.arrival_photos ?? []), path];
      const updated = await patchTask(token, task.id, {
        status: 'sahada',
        dispatched_at: new Date().toISOString(),
        arrival_photos: arrival,
      });
      setTask(updated);
      Alert.alert('Tamam', 'Görev sahada olarak işaretlendi.');
    } catch (e) {
      Alert.alert('Hata', e instanceof Error ? e.message : 'İşlem başarısız');
    } finally {
      setBusy(false);
    }
  }

  async function completeTask() {
    if (!token || !task) return;
    setBusy(true);
    try {
      const uri = await pickPhoto();
      if (!uri) return;
      const path = await uploadTaskPhoto(token, uri, 'completion');
      const arrival = task.arrival_photos ?? [];
      const completion = [...(task.completion_photos ?? []), path];
      const now = new Date().toISOString();
      const updated = await patchTask(token, task.id, {
        status: 'cozuldu',
        resolved_at: now,
        solution_note: solutionNote.trim() || 'Sahada tamamlandı.',
        arrival_photos: arrival,
        completion_photos: completion,
        ...(task.dispatched_at ? {} : { dispatched_at: now }),
        ...(task.assigned_at ? {} : { assigned_at: now }),
      });
      setTask(updated);
      Alert.alert('Tamam', 'Görev tamamlandı olarak kaydedildi.');
    } catch (e) {
      Alert.alert('Hata', e instanceof Error ? e.message : 'İşlem başarısız');
    } finally {
      setBusy(false);
    }
  }

  function openMaps() {
    if (!task?.latitude || !task?.longitude) {
      if (task?.location) {
        Linking.openURL(
          `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(task.location)}`,
        );
      }
      return;
    }
    Linking.openURL(
      `https://www.google.com/maps/dir/?api=1&destination=${task.latitude},${task.longitude}`,
    );
  }

  if (loading || !task) {
    return (
      <View style={[sharedStyles.screen, { justifyContent: 'center' }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  const closed = ['kapatildi', 'tamamlandi', 'cozuldu', 'onay_bekliyor'].includes(task.status);
  const canAct = !readOnly && !closed;

  return (
    <ScrollView style={sharedStyles.screen} contentContainerStyle={sharedStyles.container}>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', gap: 8 }}>
        <Text style={[sharedStyles.cardMeta, { fontWeight: '700' }]}>{task.task_code}</Text>
        <PriorityBadge priority={task.priority} />
      </View>
      <Text style={[sharedStyles.title, { marginTop: 4 }]}>{task.title}</Text>

      <View style={sharedStyles.card}>
        <View style={sharedStyles.cardRow}>
          <StatusBadge
            status={task.status}
            label={STATUS_LABELS[task.status] ?? task.status}
          />
        </View>
        {readOnly ? (
          <>
            <Text style={[sharedStyles.cardMeta, { marginTop: 12 }]}>
              Müdürlük: {task.department.name}
            </Text>
            {task.assignee?.name ? (
              <Text style={sharedStyles.cardMeta}>Atanan: {task.assignee.name}</Text>
            ) : null}
          </>
        ) : null}
        <Text style={[sharedStyles.cardMeta, { marginTop: 10 }]}>
          📍 {task.location ?? '—'}
        </Text>
        {task.description ? (
          <Text style={[sharedStyles.cardMeta, { marginTop: 8, lineHeight: 20 }]}>
            {task.description}
          </Text>
        ) : null}
      </View>

      <Pressable
        style={[sharedStyles.button, sharedStyles.buttonSecondary]}
        onPress={openMaps}
      >
        <Text style={sharedStyles.buttonTextDark}>🗺️ Yol tarifi</Text>
      </Pressable>

      {canAct && (
        <>
          {(task.status === 'yonlendirildi' || task.status === 'bekliyor') && (
            <Pressable style={sharedStyles.button} onPress={markOnSite} disabled={busy}>
              <Text style={sharedStyles.buttonText}>
                {busy ? '...' : 'Sahadayım (varış fotoğrafı)'}
              </Text>
            </Pressable>
          )}

          {(task.status === 'sahada' || task.status === 'yonlendirildi') && (
            <>
              <TextInput
                style={sharedStyles.input}
                value={solutionNote}
                onChangeText={setSolutionNote}
                placeholder="Çözüm notu"
                multiline
              />
              <Pressable style={sharedStyles.button} onPress={completeTask} disabled={busy}>
                <Text style={sharedStyles.buttonText}>
                  {busy ? '...' : 'Görevi tamamla (iş sonrası fotoğraf)'}
                </Text>
              </Pressable>
            </>
          )}
        </>
      )}
    </ScrollView>
  );
}
