import { Pressable, Text, View } from 'react-native';
import { PriorityBadge, StatusBadge, sharedStyles } from '../theme';
import { STATUS_LABELS, type Task } from '../types';

type Props = {
  task: Task;
  showDepartment?: boolean;
  onPress: () => void;
};

export function TaskCard({ task, showDepartment, onPress }: Props) {
  return (
    <Pressable style={sharedStyles.card} onPress={onPress}>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', gap: 8 }}>
        <Text style={[sharedStyles.cardMeta, { fontWeight: '600', color: '#94a3b8' }]}>
          {task.task_code}
        </Text>
        <PriorityBadge priority={task.priority} />
      </View>
      <Text style={[sharedStyles.cardTitle, { marginTop: 6 }]}>{task.title}</Text>
      <Text style={sharedStyles.cardMeta} numberOfLines={1}>
        📍 {task.location ?? 'Konum belirtilmedi'}
      </Text>
      {showDepartment ? (
        <Text style={[sharedStyles.cardMeta, { marginTop: 4 }]}>
          {task.department.name}
          {task.assignee?.name ? ` · ${task.assignee.name}` : ''}
        </Text>
      ) : null}
      <View style={sharedStyles.cardRow}>
        <StatusBadge
          status={task.status}
          label={STATUS_LABELS[task.status] ?? task.status}
        />
      </View>
    </Pressable>
  );
}
