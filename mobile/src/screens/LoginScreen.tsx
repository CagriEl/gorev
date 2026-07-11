import { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { colors, sharedStyles } from '../theme';

export function LoginScreen() {
  const { signIn } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleLogin() {
    setError(null);
    setLoading(true);
    try {
      await signIn(email.trim(), password);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Giriş başarısız');
    } finally {
      setLoading(false);
    }
  }

  return (
    <KeyboardAvoidingView
      style={sharedStyles.screen}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{ flexGrow: 1 }}
      >
        <SafeAreaView edges={['top']}>
          <View style={sharedStyles.loginHero}>
            <Text style={sharedStyles.loginBrand}>Bel-Sistem</Text>
            <Text style={sharedStyles.loginTagline}>
              Kırklareli Belediyesi{'\n'}saha operasyon ve raporlama
            </Text>
          </View>
        </SafeAreaView>

        <View style={sharedStyles.loginCard}>
          <Text style={[sharedStyles.sectionTitle, { fontSize: 18 }]}>Giriş yap</Text>
          <Text style={[sharedStyles.cardMeta, { marginBottom: 16 }]}>
            Personel, müdür ve başkan yardımcısı hesapları
          </Text>

          <Text style={[sharedStyles.cardMeta, { marginBottom: 6, fontWeight: '600' }]}>
            E-posta
          </Text>
          <TextInput
            style={[sharedStyles.input, { marginBottom: 14 }]}
            placeholder="ornek@kirklareli.bel.tr"
            placeholderTextColor="#94a3b8"
            autoCapitalize="none"
            keyboardType="email-address"
            value={email}
            onChangeText={setEmail}
          />

          <Text style={[sharedStyles.cardMeta, { marginBottom: 6, fontWeight: '600' }]}>
            Şifre
          </Text>
          <TextInput
            style={[sharedStyles.input, { marginBottom: 8 }]}
            placeholder="••••••••"
            placeholderTextColor="#94a3b8"
            secureTextEntry
            value={password}
            onChangeText={setPassword}
          />

          {error ? <Text style={sharedStyles.error}>{error}</Text> : null}

          <Pressable
            style={[sharedStyles.button, { marginTop: 16 }]}
            onPress={handleLogin}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={sharedStyles.buttonText}>Giriş yap</Text>
            )}
          </Pressable>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
