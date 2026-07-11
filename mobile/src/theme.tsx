import { StyleSheet, Text, View, type TextStyle, type ViewStyle } from 'react-native';

export const colors = {
  bg: '#f1f5f9',
  surface: '#ffffff',
  primary: '#2563eb',
  primaryDark: '#1e40af',
  primarySoft: '#eff6ff',
  text: '#0f172a',
  muted: '#64748b',
  border: '#e2e8f0',
  success: '#059669',
  successSoft: '#ecfdf5',
  warning: '#d97706',
  warningSoft: '#fffbeb',
  danger: '#dc2626',
  dangerSoft: '#fef2f2',
  shadow: '#0f172a',
};

export const statusColors: Record<string, { bg: string; text: string }> = {
  bekliyor: { bg: '#f1f5f9', text: '#475569' },
  yonlendirildi: { bg: '#e0f2fe', text: '#0369a1' },
  sahada: { bg: '#fef3c7', text: '#b45309' },
  cozuldu: { bg: '#d1fae5', text: '#047857' },
  onay_bekliyor: { bg: '#ede9fe', text: '#6d28d9' },
  kapatildi: { bg: '#e2e8f0', text: '#334155' },
  tamamlandi: { bg: '#d1fae5', text: '#047857' },
};

export const priorityColors: Record<string, { bg: string; text: string }> = {
  kritik: { bg: colors.dangerSoft, text: colors.danger },
  yuksek: { bg: colors.warningSoft, text: colors.warning },
  normal: { bg: colors.primarySoft, text: colors.primary },
  dusuk: { bg: '#f8fafc', text: colors.muted },
};

export const theme = { colors, statusColors, priorityColors };

export function StatusBadge({ status, label }: { status: string; label: string }) {
  const tone = statusColors[status] ?? { bg: colors.border, text: colors.muted };

  return (
    <View style={[styles.badge, { backgroundColor: tone.bg }]}>
      <Text style={[styles.badgeText, { color: tone.text }]}>{label}</Text>
    </View>
  );
}

export function PriorityBadge({ priority }: { priority: string }) {
  const key = priority.toLowerCase();
  const tone = priorityColors[key] ?? priorityColors.normal;
  const label =
    key === 'kritik'
      ? 'Kritik'
      : key === 'yuksek'
        ? 'Yüksek'
        : key === 'dusuk'
          ? 'Düşük'
          : 'Normal';

  return (
    <View style={[styles.badge, { backgroundColor: tone.bg }]}>
      <Text style={[styles.badgeText, { color: tone.text }]}>{label}</Text>
    </View>
  );
}

export function StatCard({
  label,
  value,
  tone = 'default',
}: {
  label: string;
  value: string | number;
  tone?: 'default' | 'success' | 'warning' | 'danger';
}) {
  const toneColor =
    tone === 'success'
      ? colors.success
      : tone === 'warning'
        ? colors.warning
        : tone === 'danger'
          ? colors.danger
          : colors.primary;

  const softBg =
    tone === 'success'
      ? colors.successSoft
      : tone === 'warning'
        ? colors.warningSoft
        : tone === 'danger'
          ? colors.dangerSoft
          : colors.primarySoft;

  return (
    <View style={[styles.statCard, { backgroundColor: softBg }]}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={[styles.statValue, { color: toneColor }]}>{value}</Text>
    </View>
  );
}

export function ScreenHeader({
  title,
  subtitle,
  right,
}: {
  title: string;
  subtitle?: string;
  right?: React.ReactNode;
}) {
  return (
    <View style={styles.header}>
      <View style={styles.headerTextWrap}>
        <Text style={styles.headerTitle}>{title}</Text>
        {subtitle ? <Text style={styles.headerSubtitle}>{subtitle}</Text> : null}
      </View>
      {right}
    </View>
  );
}

const shadow: ViewStyle = {
  shadowColor: colors.shadow,
  shadowOffset: { width: 0, height: 2 },
  shadowOpacity: 0.06,
  shadowRadius: 8,
  elevation: 2,
};

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 999,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.2,
  },
  statCard: {
    borderRadius: 16,
    padding: 16,
    flex: 1,
    minWidth: '46%',
  },
  statLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.muted,
    marginBottom: 6,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  statValue: {
    fontSize: 30,
    fontWeight: '800',
  },
  header: {
    backgroundColor: colors.primary,
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 20,
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
  },
  headerTextWrap: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 26,
    fontWeight: '800',
    color: '#fff',
  },
  headerSubtitle: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.85)',
    marginTop: 4,
  },
});

export const sharedStyles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: colors.bg,
  },
  container: {
    padding: 16,
    gap: 12,
  },
  contentBelowHeader: {
    padding: 16,
    gap: 12,
    marginTop: -12,
  },
  title: {
    fontSize: 24,
    fontWeight: '800',
    color: colors.text,
  },
  subtitle: {
    fontSize: 14,
    color: colors.muted,
    marginBottom: 4,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    ...shadow,
  },
  cardTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: colors.text,
    marginBottom: 4,
    lineHeight: 22,
  },
  cardMeta: {
    fontSize: 13,
    color: colors.muted,
    lineHeight: 18,
  },
  cardRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 10,
  },
  badge: styles.badge,
  badgeText: styles.badgeText as TextStyle,
  button: {
    backgroundColor: colors.primary,
    borderRadius: 14,
    paddingVertical: 15,
    alignItems: 'center',
    ...shadow,
  },
  buttonSecondary: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
  },
  buttonDanger: {
    backgroundColor: colors.danger,
  },
  buttonGhost: {
    backgroundColor: 'transparent',
    shadowOpacity: 0,
    elevation: 0,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
  buttonTextDark: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
  },
  buttonTextPrimary: {
    color: colors.primary,
    fontSize: 15,
    fontWeight: '700',
  },
  input: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 16,
    color: colors.text,
  },
  error: {
    color: colors.danger,
    fontSize: 14,
    marginTop: 8,
  },
  row: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  filterChip: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 999,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
  },
  filterChipActive: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },
  filterChipText: {
    fontSize: 13,
    fontWeight: '600',
    color: colors.muted,
  },
  filterChipTextActive: {
    color: '#fff',
  },
  loginHero: {
    backgroundColor: colors.primary,
    paddingTop: 48,
    paddingBottom: 56,
    paddingHorizontal: 24,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
  },
  loginCard: {
    marginTop: -32,
    marginHorizontal: 20,
    backgroundColor: colors.surface,
    borderRadius: 20,
    padding: 24,
    ...shadow,
    shadowOpacity: 0.1,
    shadowRadius: 16,
    elevation: 4,
  },
  loginBrand: {
    fontSize: 28,
    fontWeight: '800',
    color: '#fff',
  },
  loginTagline: {
    fontSize: 15,
    color: 'rgba(255,255,255,0.9)',
    marginTop: 6,
    lineHeight: 22,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: colors.text,
    marginBottom: 10,
  },
  deptRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  deptBar: {
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.border,
    marginTop: 6,
    overflow: 'hidden',
  },
  deptBarFill: {
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.primary,
  },
});
