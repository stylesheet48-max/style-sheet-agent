import { useRouter } from "expo-router";
import { Check, ChevronLeft, Crown, Shield, ShieldCheck, Zap } from "lucide-react-native";
import { Pressable, Text, View } from "react-native";

import { Container } from "@/components/shared/container";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { useTheme } from "@/hooks/use-theme";
import { useAuth, type UserPlan } from "@/providers/auth-state";

export default function PlanScreen() {
  const router = useRouter();
  const theme = useTheme();
  const { user } = useAuth();

  const plans: {
    key: UserPlan;
    title: string;
    badge: string;
    price: string;
    features: string[];
    models: string[];
  }[] = [
    {
      key: "free",
      title: "Free Explorer",
      badge: "Community",
      price: "$0 / month",
      features: ["50 daily requests", "16k context window", "Basic templates & skills", "2 active MCP bridges"],
      models: ["GPT-4o-mini", "Gemini 2.0 Flash", "Claude 3 Haiku"],
    },
    {
      key: "premium",
      title: "Premium Pro",
      badge: "Recommended",
      price: "$14.99 / month",
      features: [
        "500 daily requests",
        "64k context window",
        "All Marketplace packages",
        "Autonomous code agents",
        "Whisper voice engine",
        "10 MCP server bridges",
      ],
      models: ["Claude 3.5 Sonnet", "GPT-4o", "DeepSeek V3", "Gemini 2.0 Flash"],
    },
    {
      key: "ultra",
      title: "Ultra Architect",
      badge: "Enterprise",
      price: "$39.99 / month",
      features: [
        "2,500 daily requests",
        "200k max context window",
        "Unlimited MCP connections",
        "Developer Profiler & CI tools",
        "Zero token throttling",
        "Direct model fallback access",
      ],
      models: ["OpenAI o1 / o3-mini", "Claude 3.5 Sonnet", "DeepSeek R1", "Gemini 2.0 Pro"],
    },
  ];

  return (
    <Container scroll contentClassName="gap-sp-4 py-sp-4" includeBottomTabInset={false}>
      {/* Header */}
      <View className="flex-row items-center gap-sp-2">
        <Button
          leftIcon={<ChevronLeft color={theme.text} size={18} />}
          onPress={() => router.dismissTo("/")}
          size="icon-xs"
          variant="ghost"
        />
        <Text className="font-sans text-xl font-bold text-foreground dark:text-foreground-dark">
          Plan & Version
        </Text>
      </View>

      {/* Active Plan Overview */}
      <Card className="p-sp-4 bg-surface-elevated/40 border border-primary/30 rounded-3xl gap-sp-3">
        <View className="flex-row justify-between items-start">
          <View>
            <Text className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Your Active Subscription
            </Text>
            <Text className="text-2xl font-black text-foreground dark:text-foreground-dark mt-0.5">
              {user.plan_name ?? user.plan_id.toUpperCase()}
            </Text>
          </View>
          <View className="px-3 py-1 rounded-full bg-primary/20 border border-primary/50">
            <Text className="text-xs font-bold text-primary uppercase">{user.plan_id}</Text>
          </View>
        </View>

        <Text className="text-xs text-muted-foreground leading-relaxed">
          Plan verified by STYLE SHEET AGENT backend. Entitlements are validated for all Marketplace downloads and AI model queries.
        </Text>
      </Card>

      {/* Plan Tiers */}
      <Text className="text-xs font-bold uppercase tracking-wider text-muted-foreground px-1 mt-2">
        Available Subscription Tiers
      </Text>

      {plans.map((p) => {
        const isCurrent = user.plan_id === p.key;
        return (
          <Card
            key={p.key}
            className={`p-sp-4 rounded-3xl border transition ${
              isCurrent
                ? "bg-primary/10 border-primary/60 shadow-lg shadow-primary/20"
                : "bg-surface-elevated/30 border-border/40"
            }`}
          >
            <View className="flex-row justify-between items-center mb-2">
              <View>
                <Text className="font-sans text-lg font-bold text-foreground dark:text-foreground-dark">
                  {p.title}
                </Text>
                <Text className="text-xs text-primary font-mono font-semibold">{p.price}</Text>
              </View>
              {isCurrent ? (
                <View className="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/40">
                  <Text className="text-[10px] font-bold text-emerald-400">CURRENT PLAN</Text>
                </View>
              ) : (
                <View className="px-2.5 py-0.5 rounded-full bg-surface-elevated border border-border">
                  <Text className="text-[10px] font-semibold text-muted-foreground">{p.badge}</Text>
                </View>
              )}
            </View>

            <Separator className="my-2" />

            <View className="gap-1.5 my-2">
              {p.features.map((feat) => (
                <View key={feat} className="flex-row items-center gap-2">
                  <Check color={theme.accent ?? "#38BDF8"} size={13} />
                  <Text className="text-xs text-slate-300 font-sans">{feat}</Text>
                </View>
              ))}
            </View>

            <View className="pt-2">
              <Text className="text-[11px] font-semibold text-muted-foreground mb-1">Supported AI Models:</Text>
              <Text className="text-xs font-mono text-primary/90">{p.models.join(" • ")}</Text>
            </View>

            {!isCurrent && (
              <Button
                className="mt-4 w-full bg-primary"
                onPress={() => alert(`Upgrading to ${p.title}. Request sent to Admin Panel.`)}
              >
                <Text className="text-xs font-bold text-background uppercase">
                  Upgrade to {p.title}
                </Text>
              </Button>
            )}
          </Card>
        );
      })}

      {/* Version Details Card */}
      <Card className="p-sp-4 bg-surface-elevated/40 border border-border/40 rounded-3xl gap-sp-2 mt-2">
        <Text className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
          Application Build Details
        </Text>
        <View className="flex-row justify-between items-center pt-1">
          <Text className="text-xs text-muted-foreground">App Version:</Text>
          <Text className="text-xs font-bold text-foreground">v2.1.8 (Release)</Text>
        </View>
        <View className="flex-row justify-between items-center">
          <Text className="text-xs text-muted-foreground">Version Code:</Text>
          <Text className="text-xs font-mono text-foreground">2</Text>
        </View>
        <View className="flex-row justify-between items-center">
          <Text className="text-xs text-muted-foreground">Package Identifier:</Text>
          <Text className="text-xs font-mono text-primary font-bold">com.stylesheet.agent</Text>
        </View>
      </Card>
    </Container>
  );
}
