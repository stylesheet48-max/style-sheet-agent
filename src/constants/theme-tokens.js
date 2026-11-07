const Colors = {
  light: {
    text: "#071328",
    background: "#F0F6FC",
    backgroundElement: "#FFFFFF",
    backgroundSelected: "#E0F2FE",
    textSecondary: "#475E7C",
    border: "#BAE6FD",
    input: "#FFFFFF",
    ring: "#0284C7",
    accent: "#E0F2FE",
    accentForeground: "#0284C7",
    syntaxComment: "#5E718D",
    syntaxConstant: "#0369A1",
    syntaxFunction: "#0284C7",
    syntaxKeyword: "#4F46E5",
    syntaxNumber: "#B45309",
    syntaxOperator: "#0891B2",
    syntaxRegex: "#B45309",
    syntaxString: "#059669",
    destructive: "#DC2626",
    destructiveForeground: "#FFFFFF",
  },
  dark: {
    text: "#FFFFFF",
    background: "#070B14",
    backgroundElement: "#0E172A",
    backgroundSelected: "#162846",
    textSecondary: "#93C5FD",
    border: "#1E3A5F",
    input: "#0B1322",
    ring: "#38BDF8",
    accent: "#162846",
    accentForeground: "#38BDF8",
    syntaxComment: "#64748B",
    syntaxConstant: "#38BDF8",
    syntaxFunction: "#60A5FA",
    syntaxKeyword: "#818CF8",
    syntaxNumber: "#F472B6",
    syntaxOperator: "#22D3EE",
    syntaxRegex: "#FCD34D",
    syntaxString: "#34D399",
    destructive: "#F43F5E",
    destructiveForeground: "#FFFFFF",
  },
};

const Spacing = {
  half: 2,
  one: 4,
  two: 8,
  three: 16,
  four: 24,
  five: 32,
  six: 64,
};

const MaxContentWidth = 800;

module.exports = {
  Colors,
  MaxContentWidth,
  Spacing,
};
