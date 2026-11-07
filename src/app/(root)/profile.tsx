import { useRouter } from "expo-router";
import { ChevronLeft, Edit2, Key, LogOut, ShieldCheck, User } from "lucide-react-native";
import { useState } from "react";
import { Pressable, Text, TextInput, View } from "react-native";

import { Container } from "@/components/shared/container";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { useTheme } from "@/hooks/use-theme";
import { useAuth } from "@/providers/auth-state";

export default function ProfileScreen() {
  const router = useRouter();
  const theme = useTheme();
  const { user, logout } = useAuth();

  const [isEditing, setIsEditing] = useState(false);
  const [nameInput, setNameInput] = useState(user.full_name);

  return (
    <Container scroll contentClassName="gap-sp-4 py-sp-4" includeBottomTabInset={false}>
      {/* Header */}
      <View className="flex-row items-center justify-between">
        <View className="flex-row items-center gap-sp-2">
          <Button
            leftIcon={<ChevronLeft color={theme.text} size={18} />}
            onPress={() => router.dismissTo("/")}
            size="icon-xs"
            variant="ghost"
          />
          <Text className="font-sans text-xl font-bold text-foreground dark:text-foreground-dark">
            User Profile
          </Text>
        </View>

        {!user.is_guest && (
          <Button
            size="sm"
            variant="outline"
            leftIcon={<LogOut color={theme.destructive} size={14} />}
            onPress={() => {
              logout();
              router.dismissTo("/");
            }}
          >
            <Text className="text-xs text-destructive dark:text-destructive-dark font-semibold">Sign Out</Text>
          </Button>
        )}
      </View>

      {/* Profile Identity Card */}
      <Card className="p-sp-4 items-center gap-sp-3 bg-surface-elevated/40 border border-primary/20 rounded-3xl">
        <View className="w-20 h-20 rounded-full bg-primary/20 border-2 border-primary/60 items-center justify-center shadow-lg shadow-primary/30">
          <User color={theme.accent ?? "#38BDF8"} size={36} />
        </View>

        <View className="items-center">
          <Text className="font-sans text-lg font-bold text-foreground dark:text-foreground-dark">
            {user.full_name}
          </Text>
          <Text className="font-sans text-xs text-muted-foreground dark:text-muted-foreground-dark">
            {user.email}
          </Text>
        </View>

        <View className="flex-row items-center gap-2 mt-1">
          <View className="px-3 py-1 rounded-full bg-primary/15 border border-primary/40 flex-row items-center gap-1.5">
            <ShieldCheck color={theme.accent ?? "#38BDF8"} size={13} />
            <Text className="font-mono text-xs font-bold text-primary uppercase">
              {user.plan_name ?? user.plan_id}
            </Text>
          </View>

          <View className="px-2.5 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30">
            <Text className="text-[10px] font-bold text-emerald-400 uppercase">
              {user.status ?? "Active"}
            </Text>
          </View>
        </View>
      </Card>

      {/* Account Details */}
      <Card className="overflow-hidden bg-surface-elevated/40 border border-border/40 rounded-2xl">
        <View className="p-sp-3 flex-row justify-between items-center">
          <Text className="text-xs text-muted-foreground dark:text-muted-foreground-dark">Account UID</Text>
          <Text className="font-mono text-xs text-foreground dark:text-foreground-dark">{user.uid}</Text>
        </View>
        <Separator />
        <View className="p-sp-3 flex-row justify-between items-center">
          <Text className="text-xs text-muted-foreground dark:text-muted-foreground-dark">Subscription Tier</Text>
          <Text className="font-sans text-xs font-bold text-primary uppercase">{user.plan_id}</Text>
        </View>
        <Separator />
        <View className="p-sp-3 flex-row justify-between items-center">
          <Text className="text-xs text-muted-foreground dark:text-muted-foreground-dark">Expiry Date</Text>
          <Text className="font-sans text-xs text-foreground dark:text-foreground-dark">
            {user.plan_expires_at ? new Date(user.plan_expires_at).toLocaleDateString() : "Never (Local Mode)"}
          </Text>
        </View>
        <Separator />
        <View className="p-sp-3 flex-row justify-between items-center">
          <Text className="text-xs text-muted-foreground dark:text-muted-foreground-dark">Member Since</Text>
          <Text className="font-sans text-xs text-foreground dark:text-foreground-dark">
            {user.created_at ? new Date(user.created_at).toLocaleDateString() : "Active Session"}
          </Text>
        </View>
      </Card>

      {/* Action Buttons */}
      <View className="gap-sp-3">
        {user.is_guest ? (
          <Button
            className="w-full bg-primary"
            onPress={() => router.push("/auth/login" as never)}
          >
            <Text className="font-sans text-sm font-bold text-background dark:text-background-dark">
              Sign In / Register Account
            </Text>
          </Button>
        ) : (
          <>
            <Button
              variant="outline"
              leftIcon={<Edit2 color={theme.text} size={15} />}
              onPress={() => setIsEditing(!isEditing)}
            >
              <Text className="font-sans text-sm font-semibold text-foreground dark:text-foreground-dark">
                {isEditing ? "Cancel Editing" : "Edit Profile"}
              </Text>
            </Button>

            {isEditing && (
              <Card className="p-sp-3 gap-sp-2 bg-surface-elevated/40 border border-primary/20 rounded-2xl">
                <Text className="text-xs font-semibold text-muted-foreground">Full Name</Text>
                <TextInput
                  value={nameInput}
                  onChangeText={setNameInput}
                  className="bg-surface/80 border border-border rounded-xl px-3 py-2 text-sm text-foreground"
                />
                <Button size="sm" className="mt-1" onPress={() => setIsEditing(false)}>
                  <Text className="text-xs font-bold text-background">Save Changes</Text>
                </Button>
              </Card>
            )}

            <Button
              variant="outline"
              leftIcon={<Key color={theme.text} size={15} />}
              onPress={() => alert("Password change form ready.")}
            >
              <Text className="font-sans text-sm font-semibold text-foreground dark:text-foreground-dark">
                Change Password
              </Text>
            </Button>
          </>
        )}
      </View>
    </Container>
  );
}
