import React from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity } from 'react-native';

interface HeaderProps {
  searchQuery: string;
  onSearchChange: (query: string) => void;
  onSearchSubmit: () => void;
  onClearSearch: () => void;
  onOpenConfig: () => void;
  isDemoMode: boolean;
}

export const Header: React.FC<HeaderProps> = ({
  searchQuery,
  onSearchChange,
  onSearchSubmit,
  onClearSearch,
  onOpenConfig,
  isDemoMode,
}) => {
  return (
    <View style={styles.container}>
      {/* Brand & Config Bar */}
      <View style={styles.topBar}>
        <View style={styles.logoGroup}>
          <Text style={styles.brandTitle}>Guised Up</Text>
          <View style={styles.taglineBadge}>
            <Text style={styles.taglineText}>REAL CONNECTIONS</Text>
          </View>
        </View>

        <TouchableOpacity style={styles.configButton} onPress={onOpenConfig}>
          <Text style={styles.configIcon}>⚙️</Text>
          <View style={[styles.statusDot, isDemoMode ? styles.dotDemo : styles.dotLive]} />
        </TouchableOpacity>
      </View>

      {/* Semantic Search Input Box */}
      <View style={styles.searchContainer}>
        <Text style={styles.searchIcon}>🔍</Text>
        <TextInput
          style={styles.searchInput}
          placeholder="Natural search: e.g. 'funny travel stories'..."
          placeholderTextColor="#64748B"
          value={searchQuery}
          onChangeText={onSearchChange}
          onSubmitEditing={onSearchSubmit}
          returnKeyType="search"
        />
        {searchQuery.length > 0 && (
          <TouchableOpacity onPress={onClearSearch} style={styles.clearButton}>
            <Text style={styles.clearIcon}>✕</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    paddingTop: 52,
    paddingHorizontal: 16,
    paddingBottom: 16,
    backgroundColor: '#0A0D14',
    borderBottomWidth: 1,
    borderBottomColor: '#1A212E',
  },
  topBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  logoGroup: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  brandTitle: {
    color: '#E6683B',
    fontSize: 26,
    fontWeight: '900',
    letterSpacing: -0.5,
  },
  taglineBadge: {
    marginLeft: 10,
    backgroundColor: '#1E2738',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#2D374E',
  },
  taglineText: {
    color: '#94A3B8',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  configButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#161C27',
    padding: 8,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#242D3F',
  },
  configIcon: {
    fontSize: 18,
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginLeft: 6,
  },
  dotLive: {
    backgroundColor: '#10B981', // green
  },
  dotDemo: {
    backgroundColor: '#F59E0B', // amber
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#131822',
    borderRadius: 14,
    paddingHorizontal: 12,
    height: 46,
    borderWidth: 1,
    borderColor: '#222B3D',
  },
  searchIcon: {
    fontSize: 16,
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    color: '#F8FAFC',
    fontSize: 14,
  },
  clearButton: {
    padding: 4,
  },
  clearIcon: {
    color: '#64748B',
    fontSize: 14,
    fontWeight: '700',
  },
});
