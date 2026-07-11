import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  Text,
  View,
} from 'react-native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { SafeAreaView } from 'react-native-safe-area-context';
import { fetchTasks } from '../api/client';
import { TaskCard } from '../components/TaskCard';
import { useAuth } from '../auth/AuthContext';
import type { TaskStackParamList } from '../navigation/types';
import { ScreenHeader, colors, sharedStyles } from '../theme';
import type { Task } from '../types';

type Props = {
  oversight?: boolean;
};

const CLOSED = new Set(['kapatildi', 'tamamlandi']);

type FilterKey = 'all' | 'critical' | 'field';

const FILTERS: { key: FilterKey; label: string }[] = [
  { key: 'all', label: 'Tümü' },
  { key: 'critical', label: 'Kritik' },
  { key: 'field', label: 'Sahada' },
];

export function TasksScreen({ oversight = false }: Props) {
  const navigation = useNavigation<NativeStackNavigationProp<TaskStackParamList, 'Tasks'>>();
  const { token, user, signOut } = useAuth();
  const [tasks, setTasks] = useState<Task[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filter, setFilter] = useState<FilterKey>('all');

  const load = useCallback(async () => {
    if (!token) return;
    const all = await fetchTasks(token);
    setTasks(all.filter((t) => !CLOSED.has(t.status)));
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      load()
        .catch(() => setTasks([]))
        .finally(() => setLoading(false));
    }, [load]),
  );

  async function onRefresh() {
    setRefreshing(true);
    try {
      await load();
    } finally {
      setRefreshing(false);
    }
  }

  const filtered = useMemo(() => {
    if (filter === 'critical') {
      return tasks.filter((t) => t.priority.toLowerCase() === 'kritik');
    }
    if (filter === 'field') {
      return tasks.filter((t) => t.status === 'sahada');
    }
    return tasks;
  }, [tasks, filter]);

  if (loading) {
    return (
      <View style={[sharedStyles.screen, { justifyContent: 'center' }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={sharedStyles.screen}>
      <SafeAreaView edges={['top']} style={{ backgroundColor: colors.primary }}>
        <ScreenHeader
          title={oversight ? 'Görevler' : 'Görevlerim'}
          subtitle={user?.name}
          right={
            <Pressable onPress={signOut} hitSlop={8}>
              <Text style={{ color: 'rgba(255,255,255,0.95)', fontWeight: '700', fontSize: 15 }}>
                Çıkış
              </Text>
            </Pressable>
          }
        />
      </SafeAreaView>

      <View style={{ paddingHorizontal: 16, paddingTop: 14, paddingBottom: 4 }}>
        <Text style={sharedStyles.cardMeta}>
          {filtered.length} açık görev
          {oversight ? ' · bağlı müdürlükler' : ''}
        </Text>
        <View style={[sharedStyles.row, { marginTop: 10 }]}>
          {FILTERS.map((item) => {
            const active = filter === item.key;
            return (
              <Pressable
                key={item.key}
                style={[sharedStyles.filterChip, active && sharedStyles.filterChipActive]}
                onPress={() => setFilter(item.key)}
              >
                <Text
                  style={[
                    sharedStyles.filterChipText,
                    active && sharedStyles.filterChipTextActive,
                  ]}
                >
                  {item.label}
                </Text>
              </Pressable>
            );
          })}
        </View>
      </View>

      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: 16, paddingTop: 8, gap: 12, flexGrow: 1 }}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
        }
        ListEmptyComponent={
          <View style={[sharedStyles.card, { alignItems: 'center', paddingVertical: 32 }]}>
            <Text style={{ fontSize: 32, marginBottom: 8 }}>✓</Text>
            <Text style={[sharedStyles.cardTitle, { textAlign: 'center' }]}>
              Açık görev yok
            </Text>
            <Text style={[sharedStyles.cardMeta, { textAlign: 'center' }]}>
              {filter === 'all'
                ? 'Tüm görevler tamamlanmış görünüyor.'
                : 'Bu filtrede görev bulunamadı.'}
            </Text>
          </View>
        }
        renderItem={({ item }) => (
          <TaskCard
            task={item}
            showDepartment={oversight}
            onPress={() => navigation.navigate('TaskDetail', { taskId: item.id })}
          />
        )}
      />
    </View>
  );
}
