import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { TaskDetailScreen } from '../screens/TaskDetailScreen';
import { TasksScreen } from '../screens/TasksScreen';
import type { TaskStackParamList } from './types';
import { colors } from '../theme';

const TaskStack = createNativeStackNavigator<TaskStackParamList>();

type Props = {
  oversight?: boolean;
};

export function TaskStackNavigator({ oversight = false }: Props) {
  return (
    <TaskStack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
        headerShadowVisible: false,
      }}
    >
      <TaskStack.Screen name="Tasks" options={{ headerShown: false }}>
        {() => <TasksScreen oversight={oversight} />}
      </TaskStack.Screen>
      <TaskStack.Screen
        name="TaskDetail"
        component={TaskDetailScreen}
        options={{
          title: 'Görev detayı',
          headerBackTitle: 'Geri',
        }}
      />
    </TaskStack.Navigator>
  );
}
