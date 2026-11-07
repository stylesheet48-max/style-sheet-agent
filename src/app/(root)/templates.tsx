import { useRouter } from "expo-router";
import {
  BookOpen,
  Box,
  Calculator,
  ChevronLeft,
  Cpu,
  Database,
  Download,
  Gauge,
  GitBranch,
  Layers,
  Search,
  ShieldAlert,
  Sparkles,
} from "lucide-react-native";
import { useState, useMemo } from "react";
import { Pressable, ScrollView, Text, TextInput, View } from "react-native";

import { Container } from "@/components/shared/container";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { useTheme } from "@/hooks/use-theme";
import { useAuth } from "@/providers/auth-state";

export type MarketplacePackage = {
  id: string;
  category: string;
  title: string;
  description: string;
  version: string;
  author: string;
  iconName: string;
  eligiblePlans: string[];
  permissions: string[];
};

const PACKAGES_CATALOG: MarketplacePackage[] = [
  {
    id: "pkg-glass-ui",
    category: "Templates",
    title: "Glassmorphism Design System",
    description: "Frosted glass tokens, blur cards, and neon cyan glow border components.",
    version: "1.2.0",
    author: "STYLE SHEET LABS",
    iconName: "Layers",
    eligiblePlans: ["free", "premium", "ultra"],
    permissions: ["filesystem", "theme_override"],
  },
  {
    id: "pkg-git-flow",
    category: "Skills",
    title: "Autonomous Git Workflow",
    description: "Automated git branch sync, semantic commit generation, and conflict resolution.",
    version: "2.0.1",
    author: "DevOps Core",
    iconName: "GitBranch",
    eligiblePlans: ["free", "premium", "ultra"],
    permissions: ["terminal_exec", "git_repo"],
  },
  {
    id: "pkg-postgres-mcp",
    category: "MCP Servers",
    title: "PostgreSQL Cloud MCP Server",
    description: "Direct database schema introspection and query tools for AI agents.",
    version: "1.0.4",
    author: "DataStack",
    iconName: "Database",
    eligiblePlans: ["premium", "ultra"],
    permissions: ["network_access", "mcp_bridge"],
  },
  {
    id: "pkg-code-auditor",
    category: "AI Agents",
    title: "Full-Stack Code Auditor Agent",
    description: "Autonomous security review agent with OWASP Top 10 vulnerability inspection.",
    version: "3.1.0",
    author: "SecAgent Inc",
    iconName: "ShieldAlert",
    eligiblePlans: ["premium", "ultra"],
    permissions: ["file_read", "ast_parser"],
  },
  {
    id: "pkg-ci-orchestrator",
    category: "Workflows",
    title: "GitHub Actions CI Pipeline",
    description: "Automated multi-stage workflow pipeline for building Android APKs and running tests.",
    version: "1.5.0",
    author: "STYLE SHEET LABS",
    iconName: "Cpu",
    eligiblePlans: ["ultra"],
    permissions: ["cloud_api", "github_oauth"],
  },
  {
    id: "pkg-math-tools",
    category: "Plugins",
    title: "Math & Symbolic Calculus Plugin",
    description: "Python and WASM symbolic algebra and plotting tools for scientific calculations.",
    version: "1.1.2",
    author: "OpenScientific",
    iconName: "Calculator",
    eligiblePlans: ["free", "premium", "ultra"],
    permissions: ["isolated_sandbox"],
  },
  {
    id: "pkg-rn-native-kit",
    category: "Code Packages",
    title: "React Native Glassmorphism Kit",
    description: "Prebuilt TSX layout components with animated blur effects.",
    version: "2.3.0",
    author: "STYLE SHEET LABS",
    iconName: "Box",
    eligiblePlans: ["free", "premium", "ultra"],
    permissions: ["project_import"],
  },
  {
    id: "pkg-react-native-knowledge",
    category: "Knowledge Packs",
    title: "React Native 0.86 Architecture Guide",
    description: "Indexed vector knowledge pack covering Bridgeless Mode, Fabric, and TurboModules.",
    version: "1.0.0",
    author: "DocEngine",
    iconName: "BookOpen",
    eligiblePlans: ["premium", "ultra"],
    permissions: ["agent_context"],
  },
  {
    id: "pkg-profiler-tools",
    category: "Developer Tools",
    title: "Mobile Performance Profiler",
    description: "Real-time frame drops, bundle memory analysis, and JNI bridge latency inspector.",
    version: "2.1.0",
    author: "CorePerf",
    iconName: "Gauge",
    eligiblePlans: ["ultra"],
    permissions: ["profiler_hook", "memory_monitor"],
  },
];

const CATEGORIES = [
  "All",
  "Templates",
  "Plugins",
  "Skills",
  "MCP Servers",
  "AI Agents",
  "Workflows",
  "Code Packages",
  "Knowledge Packs",
  "Developer Tools",
];

export default function TemplatesMarketplaceScreen() {
  const router = useRouter();
  const theme = useTheme();
  const { user } = useAuth();

  const [selectedCategory, setSelectedCategory] = useState("All");
  const [searchQuery, setSearchQuery] = useState("");
  const [installedIds, setInstalledIds] = useState<string[]>(["pkg-glass-ui"]);

  const filteredPackages = useMemo(() => {
    return PACKAGES_CATALOG.filter((pkg) => {
      const matchCat = selectedCategory === "All" || pkg.category === selectedCategory;
      const matchSearch =
        pkg.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        pkg.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
        pkg.author.toLowerCase().includes(searchQuery.toLowerCase());
      return matchCat && matchSearch;
    });
  }, [selectedCategory, searchQuery]);

  const handleInstall = (pkg: MarketplacePackage) => {
    const isEligible = pkg.eligiblePlans.includes(user.plan_id) || user.plan_id === "ultra";
    if (!isEligible) {
      alert(
        `This package requires ${pkg.eligiblePlans.join(" or ").toUpperCase()} plan. Your current plan is ${user.plan_id.toUpperCase()}. Please upgrade to access.`
      );
      return;
    }
    setInstalledIds((prev) => [...prev, pkg.id]);
    alert(`Success: "${pkg.title}" imported safely with backup point created.`);
  };

  const getPackageIcon = (iconName: string) => {
    switch (iconName) {
      case "GitBranch":
        return <GitBranch color={theme.accent ?? "#38BDF8"} size={20} />;
      case "Database":
        return <Database color={theme.accent ?? "#38BDF8"} size={20} />;
      case "ShieldAlert":
        return <ShieldAlert color={theme.accent ?? "#38BDF8"} size={20} />;
      case "Cpu":
        return <Cpu color={theme.accent ?? "#38BDF8"} size={20} />;
      case "Calculator":
        return <Calculator color={theme.accent ?? "#38BDF8"} size={20} />;
      case "Box":
        return <Box color={theme.accent ?? "#38BDF8"} size={20} />;
      case "BookOpen":
        return <BookOpen color={theme.accent ?? "#38BDF8"} size={20} />;
      case "Gauge":
        return <Gauge color={theme.accent ?? "#38BDF8"} size={20} />;
      default:
        return <Layers color={theme.accent ?? "#38BDF8"} size={20} />;
    }
  };

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
            Templates & Marketplace
          </Text>
        </View>
        <View className="px-3 py-1 rounded-full bg-primary/15 border border-primary/40">
          <Text className="font-mono text-xs font-bold text-primary uppercase">{user.plan_id}</Text>
        </View>
      </View>

      {/* Search Input */}
      <View className="flex-row items-center bg-surface-elevated/50 border border-border/50 rounded-2xl px-3 py-2 gap-2">
        <Search color={theme.textSecondary} size={16} />
        <TextInput
          placeholder="Search templates, plugins, skills, MCP..."
          placeholderTextColor={theme.textSecondary}
          value={searchQuery}
          onChangeText={setSearchQuery}
          className="flex-1 font-sans text-xs text-foreground"
        />
      </View>

      {/* Categories Horizontal Carousel */}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 6 }}>
        {CATEGORIES.map((cat) => {
          const isSel = selectedCategory === cat;
          return (
            <Pressable
              key={cat}
              onPress={() => setSelectedCategory(cat)}
              className={`px-3 py-1.5 rounded-xl border transition ${
                isSel
                  ? "bg-primary border-primary shadow-sm shadow-primary/30"
                  : "bg-surface-elevated/40 border-border/40"
              }`}
            >
              <Text
                className={`font-sans text-xs font-semibold ${
                  isSel ? "text-background font-bold" : "text-muted-foreground"
                }`}
              >
                {cat}
              </Text>
            </Pressable>
          );
        })}
      </ScrollView>

      {/* Sponsored Marketplace Ad Card */}
      <Card className="p-sp-3 bg-primary/5 border border-primary/30 rounded-2xl flex-row items-center gap-3">
        <View className="w-8 h-8 rounded-lg bg-primary/20 items-center justify-center">
          <Sparkles color={theme.accent ?? "#38BDF8"} size={16} />
        </View>
        <View className="flex-1">
          <View className="flex-row items-center gap-2">
            <Text className="text-[10px] font-bold uppercase tracking-wider text-primary">Sponsored</Text>
          </View>
          <Text className="text-xs font-bold text-foreground mt-0.5">STYLE SHEET CLOUD MCP</Text>
          <Text className="text-[11px] text-muted-foreground">High-performance remote agent clusters available.</Text>
        </View>
      </Card>

      {/* Package List */}
      <View className="gap-sp-3">
        {filteredPackages.map((pkg) => {
          const isInstalled = installedIds.includes(pkg.id);
          const isEligible = pkg.eligiblePlans.includes(user.plan_id) || user.plan_id === "ultra";

          return (
            <Card
              key={pkg.id}
              className="p-sp-4 bg-surface-elevated/40 border border-border/40 rounded-3xl gap-sp-2"
            >
              <View className="flex-row items-start justify-between">
                <View className="flex-row items-center gap-3 flex-1 pr-2">
                  <View className="w-10 h-10 rounded-2xl bg-primary/15 border border-primary/30 items-center justify-center">
                    {getPackageIcon(pkg.iconName)}
                  </View>
                  <View className="flex-1">
                    <Text className="font-sans text-sm font-bold text-foreground dark:text-foreground-dark">
                      {pkg.title}
                    </Text>
                    <Text className="text-[11px] text-muted-foreground font-mono">
                      v{pkg.version} &bull; {pkg.author}
                    </Text>
                  </View>
                </View>

                <View className="px-2 py-0.5 rounded-lg bg-surface border border-border">
                  <Text className="text-[10px] font-semibold text-muted-foreground">{pkg.category}</Text>
                </View>
              </View>

              <Text className="text-xs text-slate-300 leading-relaxed font-sans">{pkg.description}</Text>

              <View className="flex-row items-center justify-between pt-2 border-t border-border/30 mt-1">
                <View className="flex-row items-center gap-1.5">
                  {pkg.eligiblePlans.map((pl) => (
                    <View
                      key={pl}
                      className={`px-2 py-0.5 rounded text-[9px] font-bold ${
                        pl === "ultra"
                          ? "bg-purple-500/20 text-purple-300 border border-purple-500/30"
                          : pl === "premium"
                          ? "bg-sky-500/20 text-sky-400 border border-sky-400/30"
                          : "bg-slate-800 text-slate-400"
                      }`}
                    >
                      <Text className="text-[9px] font-bold uppercase">{pl}</Text>
                    </View>
                  ))}
                </View>

                <Button
                  size="sm"
                  className={isInstalled ? "bg-emerald-600/30 border border-emerald-500/40" : "bg-primary"}
                  onPress={() => handleInstall(pkg)}
                  disabled={isInstalled}
                >
                  <Text className="text-xs font-bold text-background">
                    {isInstalled ? "Installed" : isEligible ? "Import Package" : "Requires Upgrade"}
                  </Text>
                </Button>
              </View>
            </Card>
          );
        })}
      </View>
    </Container>
  );
}
