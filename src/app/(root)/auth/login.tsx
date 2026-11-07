import React, { useState } from "react";
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from "react-native";
import { router } from "expo-router";
import { Container } from "@/components/shared/container";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { useAuth } from "@/providers/auth-state";
import { useTheme } from "@/hooks/use-theme";
import { Bot, Mail, Lock, ChevronLeft, ArrowRight, ShieldCheck, Sparkles } from "lucide-react-native";

export default function LoginScreen() {
  const { login } = useAuth();
  const theme = useTheme();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleLogin = async () => {
    if (!email.trim() || !password.trim()) {
      Alert.alert("Required Fields", "Please enter your email and password.");
      return;
    }

    try {
      setIsSubmitting(true);
      const res = await login(email, password);
      if (res.success) {
        Alert.alert("Welcome Back!", "Successfully authenticated.", [
          { text: "Continue", onPress: () => router.replace("/" as never) },
        ]);
      } else {
        Alert.alert("Authentication Failed", res.message ?? "Invalid email or password.");
      }
    } catch (e) {
      Alert.alert("Error", (e as Error).message);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleForgotPassword = () => {
    Alert.alert(
      "Reset Password",
      "Enter your registered email address to receive password reset instructions, or contact your STYLE SHEET AGENT administrator.",
      [{ text: "OK" }]
    );
  };

  return (
    <Container scroll contentClassName="gap-sp-4 py-sp-4" includeBottomTabInset={false}>
      {/* Back button */}
      <View className="flex-row items-center justify-between">
        <Button
          leftIcon={<ChevronLeft color={theme.text} size={18} />}
          variant="ghost"
          size="sm"
          onPress={() => router.back()}
        >
          <Text className="text-xs font-semibold text-foreground dark:text-foreground-dark">Back</Text>
        </Button>
      </View>

      <KeyboardAvoidingView
        behavior={Platform.OS === "ios" ? "padding" : undefined}
        className="gap-sp-4"
      >
        {/* Branding header */}
        <View className="items-center my-4">
          <View className="w-16 h-16 rounded-3xl bg-primary/20 border-2 border-primary/50 items-center justify-center shadow-lg shadow-primary/30 mb-3">
            <Bot color={theme.accent ?? "#38BDF8"} size={32} />
          </View>
          <Text className="text-2xl font-bold tracking-tight text-foreground dark:text-foreground-dark">
            STYLE SHEET AGENT
          </Text>
          <Text className="text-xs text-muted-foreground dark:text-muted-foreground-dark mt-1 text-center px-4">
            Sign in to unlock autonomous agent workflows, custom MCP tools, and cross-platform execution.
          </Text>
        </View>

        {/* Login Form Card */}
        <Card className="p-sp-4 gap-sp-3 bg-surface-elevated/40 border border-primary/20 rounded-3xl shadow-xl">
          <View className="gap-1.5">
            <Text className="text-xs font-semibold text-foreground dark:text-foreground-dark">
              Email Address
            </Text>
            <View className="flex-row items-center bg-surface/60 border border-border/60 rounded-2xl px-3 py-2.5">
              <Mail color={theme.textSecondary} size={16} />
              <TextInput
                value={email}
                onChangeText={setEmail}
                placeholder="developer@stylesheet.agent"
                placeholderTextColor={theme.textSecondary}
                keyboardType="email-address"
                autoCapitalize="none"
                className="flex-1 ml-2.5 text-sm text-foreground dark:text-foreground-dark font-sans"
              />
            </View>
          </View>

          <View className="gap-1.5">
            <View className="flex-row items-center justify-between">
              <Text className="text-xs font-semibold text-foreground dark:text-foreground-dark">
                Password
              </Text>
              <TouchableOpacity onPress={handleForgotPassword}>
                <Text className="text-[11px] font-semibold text-primary">Forgot Password?</Text>
              </TouchableOpacity>
            </View>
            <View className="flex-row items-center bg-surface/60 border border-border/60 rounded-2xl px-3 py-2.5">
              <Lock color={theme.textSecondary} size={16} />
              <TextInput
                value={password}
                onChangeText={setPassword}
                placeholder="••••••••••••"
                placeholderTextColor={theme.textSecondary}
                secureTextEntry
                className="flex-1 ml-2.5 text-sm text-foreground dark:text-foreground-dark font-sans"
              />
            </View>
          </View>

          <Button
            className="mt-2 py-3 bg-primary rounded-2xl items-center justify-center flex-row gap-2 shadow-md shadow-primary/30"
            disabled={isSubmitting}
            onPress={handleLogin}
          >
            <Text className="text-sm font-bold text-primary-foreground">
              {isSubmitting ? "Signing in..." : "Sign In"}
            </Text>
            <ArrowRight color="#000" size={16} />
          </Button>

          {/* Quick guest action */}
          <Button
            variant="outline"
            className="rounded-2xl border-border/50 py-2.5"
            onPress={() => router.replace("/" as never)}
          >
            <Text className="text-xs font-semibold text-muted-foreground">Continue as Guest Explorer</Text>
          </Button>
        </Card>

        {/* Switch to Sign Up */}
        <View className="flex-row items-center justify-center gap-1.5 mt-2">
          <Text className="text-xs text-muted-foreground">Don't have an account?</Text>
          <TouchableOpacity onPress={() => router.push("/auth/signup" as never)}>
            <Text className="text-xs font-bold text-primary">Create Account</Text>
          </TouchableOpacity>
        </View>

        {/* Security badge */}
        <View className="flex-row items-center justify-center gap-2 mt-4 p-2.5 rounded-2xl bg-surface/30 border border-border/30">
          <ShieldCheck color={theme.accent ?? "#38BDF8"} size={14} />
          <Text className="text-[11px] text-muted-foreground text-center">
            End-to-end encrypted sessions & local-first credential storage.
          </Text>
        </View>
      </KeyboardAvoidingView>
    </Container>
  );
}
