import * as Linking from "expo-linking";
import { ExternalLink, Sparkles, X } from "lucide-react-native";
import React, { useState } from "react";
import { Pressable, Text, View } from "react-native";

import { useTheme } from "@/hooks/use-theme";

export type SponsoredAdProps = {
  placement: "chat_input" | "chat_feed" | "marketplace";
  onDismiss?: () => void;
};

export function SponsoredAdCard({ placement, onDismiss }: SponsoredAdProps) {
  const theme = useTheme();
  const [dismissed, setDismissed] = useState(false);

  if (dismissed) return null;

  const handlePress = () => {
    Linking.openURL("https://github.com/stylesheet48-max/style-sheet-agent").catch(() => {});
  };

  const handleClose = () => {
    setDismissed(true);
    onDismiss?.();
  };

  if (placement === "chat_input") {
    return (
      <View className="mx-3 mb-2 p-2.5 rounded-2xl bg-surface-elevated/70 border border-primary/25 backdrop-blur-md flex-row items-center justify-between shadow-lg shadow-black/40">
        <Pressable onPress={handlePress} className="flex-1 flex-row items-center gap-2.5 pr-2">
          <View className="w-7 h-7 rounded-lg bg-primary/20 border border-primary/40 items-center justify-center">
            <Sparkles color={theme.accent ?? "#38BDF8"} size={14} />
          </View>
          <View className="flex-1">
            <View className="flex-row items-center gap-1.5">
              <Text className="text-[9px] font-bold uppercase tracking-wider text-primary">Sponsored</Text>
              <Text className="text-xs font-bold text-foreground truncate">STYLE SHEET PRO</Text>
            </View>
            <Text numberOfLines={1} className="text-[11px] text-muted-foreground">
              Autonomous AI workflows & Claude 3.5 Sonnet ready.
            </Text>
          </View>
        </Pressable>

        <Pressable onPress={handleClose} hitSlop={8} className="p-1 rounded-full hover:bg-surface">
          <X color={theme.textSecondary} size={14} />
        </Pressable>
      </View>
    );
  }

  // placement === 'chat_feed'
  return (
    <View className="my-3 mx-4 p-3 rounded-2xl bg-surface-elevated/50 border border-primary/20 backdrop-blur-md flex-row items-center justify-between">
      <Pressable onPress={handlePress} className="flex-1 flex-row items-center gap-3 pr-2">
        <View className="w-8 h-8 rounded-xl bg-primary/20 items-center justify-center">
          <Sparkles color={theme.accent ?? "#38BDF8"} size={16} />
        </View>
        <View className="flex-1">
          <Text className="text-[9px] font-bold uppercase tracking-wider text-primary">Sponsored Partner</Text>
          <Text className="text-xs font-bold text-foreground">Cloud Postgres & Docker MCP Server</Text>
          <Text numberOfLines={1} className="text-[11px] text-muted-foreground">
            Connect your mobile AI agent to real databases securely.
          </Text>
        </View>
      </Pressable>

      <Pressable onPress={handleClose} hitSlop={8} className="p-1">
        <X color={theme.textSecondary} size={14} />
      </Pressable>
    </View>
  );
}
