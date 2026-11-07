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
import { Checkbox } from "@/components/ui/checkbox";
import { useAuth } from "@/providers/auth-state";
import { useTheme } from "@/hooks/use-theme";
import { Bot, Mail, Lock, User, ChevronLeft, ArrowRight, ShieldCheck } from "lucide-react-native";

export default function SignUpScreen() {
  const { signup } = useAuth();
  const theme = useTheme();

  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [agreeTerms, setAgreeTerms] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSignUp = async () => {
    if (!fullName.trim() || !email.trim() || !password.trim()) {
      Alert.alert("Missing Information", "Please fill in all required fields.");
      return;
    }

    if (password.length < 8) {
      Alert.alert("Weak Password", "Password must be at least 8 characters long.");
      return;
    }

    if (password !== confirmPassword) {
      Alert.alert("Password Mismatch", "Passwords do not match. Please verify.");
      return;
    }

    if (!agreeTerms) {
      Alert.alert("Terms Required", "Please accept the Terms of Service and Privacy Policy to continue.");
      return;
    }

    try {
      setIsSubmitting(true);
      const res = await signup(fullName, email, password, agreeTerms);
      if (res.success) {
        Alert.alert(
          "Account Created!",
          "Welcome to STYLE SHEET AGENT. Your workspace is ready.",
          [{ text: "Start Exploring", onPress: () => router.replace("/" as never) }]
        );
      } else {
        Alert.alert("Registration Failed", res.message ?? "Could not create account.");
      }
    } catch (e) {
      Alert.alert("Error", (e as Error).message);
    } finally {
      setIsSubmitting(false);
    }
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
        <View className="items-center my-3">
          <View className="w-14 h-14 rounded-3xl bg-primary/20 border-2 border-primary/50 items-center justify-center shadow-lg shadow-primary/30 mb-2">
            <Bot color={theme.accent ?? "#38BDF8"} size={28} />
          </View>
          <Text className="text-xl font-bold tracking-tight text-foreground dark:text-foreground-dark">
            Join STYLE SHEET AGENT
          </Text>
          <Text className="text-xs text-muted-foreground dark:text-muted-foreground-dark mt-1 text-center px-4">
            Create an independent identity to sync workflows, templates, and models.
          </Text>
        </View>

        {/* Sign Up Form Card */}
        <Card className="p-sp-4 gap-sp-3 bg-surface-elevated/40 border border-primary/20 rounded-3xl shadow-xl">
          {/* Full Name */}
          <View className="gap-1.5">
            <Text className="text-xs font-semibold text-foreground dark:text-foreground-dark">
              Full Name
            </Text>
            <View className="flex-row items-center bg-surface/60 border border-border/60 rounded-2xl px-3 py-2.5">
              <User color={theme.textSecondary} size={16} />
              <TextInput
                value={fullName}
                onChangeText={setFullName}
                placeholder="Alex Mercer"
                placeholderTextColor={theme.textSecondary}
                className="flex-1 ml-2.5 text-sm text-foreground dark:text-foreground-dark font-sans"
              />
            </View>
          </View>

          {/* Email */}
          <View className="gap-1.5">
            <Text className="text-xs font-semibold text-foreground dark:text-foreground-dark">
              Email Address
            </Text>
            <View className="flex-row items-center bg-surface/60 border border-border/60 rounded-2xl px-3 py-2.5">
              <Mail color={theme.textSecondary} size={16} />
              <TextInput
                value={email}
                onChangeText={setEmail}
                placeholder="alex@stylesheet.agent"
                placeholderTextColor={theme.textSecondary}
                keyboardType="email-address"
                autoCapitalize="none"
                className="flex-1 ml-2.5 text-sm text-foreground dark:text-foreground-dark font-sans"
              />
            </View>
          </View>

          {/* Password */}
          <View className="gap-1.5">
            <Text className="text-xs font-semibold text-foreground dark:text-foreground-dark">
              Password
            </Text>
            <View className="flex-row items-center bg-surface/60 border border-border/60 rounded-2xl px-3 py-2.5">
              <Lock color={theme.textSecondary} size={16} />
              <TextInput
                value={password}
                onChangeText={setPassword}
                placeholder="At least 8 characters"
                placeholderTextColor={theme.textSecondary}
                secureTextEntry
                className="flex-1 ml-2.5 text-sm text-foreground dark:text-foreground-dark font-sans"
              />
            </View>
          </View>

          {/* Confirm Password */}
          <View className="gap-1.5">
            <Text className="text-xs font-semibold text-foreground dark:text-foreground-dark">
              Confirm Password
            </Text>
            <View className="flex-row items-center bg-surface/60 border border-border/60 rounded-2xl px-3 py-2.5">
              <Lock color={theme.textSecondary} size={16} />
              <TextInput
                value={confirmPassword}
                onChangeText={setConfirmPassword}
                placeholder="Repeat your password"
                placeholderTextColor={theme.textSecondary}
                secureTextEntry
                className="flex-1 ml-2.5 text-sm text-foreground dark:text-foreground-dark font-sans"
              />
            </View>
          </View>

          {/* Terms Checkbox */}
          <View className="flex-row items-center gap-2.5 pt-1">
            <Checkbox checked={agreeTerms} onCheckedChange={setAgreeTerms} />
            <Text className="text-xs text-muted-foreground flex-1">
              I agree to the <Text className="text-primary font-semibold">Terms of Service</Text> and{" "}
              <Text className="text-primary font-semibold">Privacy Policy</Text>.
            </Text>
          </View>

          {/* Submit */}
          <Button
            className="mt-2 py-3 bg-primary rounded-2xl items-center justify-center flex-row gap-2 shadow-md shadow-primary/30"
            disabled={isSubmitting}
            onPress={handleSignUp}
          >
            <Text className="text-sm font-bold text-primary-foreground">
              {isSubmitting ? "Creating Account..." : "Create Free Account"}
            </Text>
            <ArrowRight color="#000" size={16} />
          </Button>
        </Card>

        {/* Switch to Login */}
        <View className="flex-row items-center justify-center gap-1.5 mt-2">
          <Text className="text-xs text-muted-foreground">Already have an account?</Text>
          <TouchableOpacity onPress={() => router.push("/auth/login" as never)}>
            <Text className="text-xs font-bold text-primary">Sign In</Text>
          </TouchableOpacity>
        </View>

        {/* Security badge */}
        <View className="flex-row items-center justify-center gap-2 mt-2 p-2.5 rounded-2xl bg-surface/30 border border-border/30">
          <ShieldCheck color={theme.accent ?? "#38BDF8"} size={14} />
          <Text className="text-[11px] text-muted-foreground text-center">
            Zero telemetry of private keys or chat transcripts without consent.
          </Text>
        </View>
      </KeyboardAvoidingView>
    </Container>
  );
}
