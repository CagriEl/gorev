import type { NavigatorScreenParams } from '@react-navigation/native';

export type TaskStackParamList = {
  Tasks: undefined;
  TaskDetail: { taskId: number };
};

export type ManagerTabParamList = {
  TasksTab: NavigatorScreenParams<TaskStackParamList>;
  ReportTab: undefined;
};

export type RootStackParamList = {
  Login: undefined;
  StaffApp: NavigatorScreenParams<TaskStackParamList> | undefined;
  ManagerApp: NavigatorScreenParams<ManagerTabParamList> | undefined;
};

/** @deprecated Use TaskStackParamList */
export type StaffStackParamList = TaskStackParamList;
