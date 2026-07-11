import { useFocusEffect } from '@react-navigation/native';
import { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { fetchReportDashboard } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { ScreenHeader, StatCard, colors, sharedStyles } from '../theme';
import { STATUS_LABELS, type ReportDashboard } from '../types';

export function ReportsScreen() {
  const { token, user, signOut } = useAuth();
  const [report, setReport] = useState<ReportDashboard | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    if (!token) return;
    setReport(await fetchReportDashboard(token));
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      load()
        .catch(() => setReport(null))
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

  if (loading) {
    return (
      <View style={[sharedStyles.screen, { justifyContent: 'center' }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  if (!report) {
    return (
      <View style={[sharedStyles.screen, sharedStyles.container]}>
        <Text style={sharedStyles.error}>Rapor yüklenemedi.</Text>
      </View>
    );
  }

  const { summary, by_status, by_department } = report;
  const maxDeptOpen = Math.max(1, ...by_department.map((d) => d.open));

  return (
    <ScrollView
      style={sharedStyles.screen}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
      }
    >
      <SafeAreaView edges={['top']} style={{ backgroundColor: colors.primary }}>
        <ScreenHeader
          title="Özet rapor"
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

      <View style={sharedStyles.contentBelowHeader}>
        <View style={sharedStyles.row}>
          <StatCard label="Açık görev" value={summary.open_tasks} />
          <StatCard label="Kritik" value={summary.critical_open} tone="danger" />
          <StatCard label="Sahada" value={summary.in_field} tone="warning" />
          <StatCard label="Bugün çözülen" value={summary.resolved_today} tone="success" />
        </View>

        {report.scope !== 'manager' && (
          <View style={sharedStyles.card}>
            <Text style={sharedStyles.sectionTitle}>Personel</Text>
            <Text style={[sharedStyles.title, { fontSize: 32 }]}>{summary.total_staff}</Text>
            <Text style={sharedStyles.cardMeta}>Bağlı müdürlüklerdeki toplam personel</Text>
          </View>
        )}

        <View style={sharedStyles.card}>
          <Text style={sharedStyles.sectionTitle}>Durum dağılımı</Text>
          {Object.entries(by_status).map(([key, count]) => (
            <View key={key} style={sharedStyles.deptRow}>
              <Text style={sharedStyles.cardMeta}>{STATUS_LABELS[key] ?? key}</Text>
              <Text style={[sharedStyles.cardTitle, { marginBottom: 0, fontSize: 16 }]}>
                {count}
              </Text>
            </View>
          ))}
        </View>

        <View style={sharedStyles.card}>
          <Text style={sharedStyles.sectionTitle}>Müdürlükler</Text>
          {by_department.map((d) => (
            <View key={d.id} style={{ marginBottom: 14 }}>
              <View style={sharedStyles.deptRow}>
                <Text style={[sharedStyles.cardMeta, { flex: 1, marginRight: 8 }]} numberOfLines={2}>
                  {d.name}
                </Text>
                <Text style={sharedStyles.cardMeta}>
                  {d.open} açık · {d.completed} tamam
                </Text>
              </View>
              <View style={sharedStyles.deptBar}>
                <View
                  style={[
                    sharedStyles.deptBarFill,
                    { width: `${Math.round((d.open / maxDeptOpen) * 100)}%` },
                  ]}
                />
              </View>
            </View>
          ))}
        </View>
      </View>
    </ScrollView>
  );
}
