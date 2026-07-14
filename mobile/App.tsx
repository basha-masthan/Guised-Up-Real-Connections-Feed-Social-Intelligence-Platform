import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet,
  View,
  Text,
  FlatList,
  ActivityIndicator,
  TouchableOpacity,
  SafeAreaView,
  StatusBar,
  RefreshControl,
  Platform,
} from 'react-native';
import { PostCard, PostItem } from './components/PostCard';
import { Header } from './components/Header';
import { ConfigModal } from './components/ConfigModal';
import { DEMO_POSTS } from './data/demoPosts';

const DEFAULT_API_URL = 'https://guised-up-real-connections-feed-social.onrender.com';

export default function App() {
  const [posts, setPosts] = useState<PostItem[]>([]);
  const [page, setPage] = useState<number>(1);
  const [loading, setLoading] = useState<boolean>(true);
  const [refreshing, setRefreshing] = useState<boolean>(false);
  const [hasMore, setHasMore] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  // Search State
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [isSearching, setIsSearching] = useState<boolean>(false);
  const [searchResults, setSearchResults] = useState<PostItem[]>([]);

  // Config & Context State
  const [apiUrl, setApiUrl] = useState<string>(DEFAULT_API_URL);
  const [isDemoMode, setIsDemoMode] = useState<boolean>(false);
  const [configVisible, setConfigVisible] = useState<boolean>(false);
  const [currentUser, setCurrentUser] = useState<{ id: number; name: string }>({
    id: 1,
    name: 'Aarav Sharma',
  });
  const [bearerToken, setBearerToken] = useState<string>('');

  // Fetch token or initialize mode
  useEffect(() => {
    initTokenAndFetch();
  }, [apiUrl, isDemoMode, currentUser]);

  const initTokenAndFetch = async () => {
    setLoading(true);
    setError(null);

    if (isDemoMode) {
      setTimeout(() => {
        setPosts(DEMO_POSTS);
        setLoading(false);
      }, 300);
      return;
    }

    let newToken = '';
    try {
      // Grab a test bearer token from public helper endpoint
      const tokenResp = await fetch(`${apiUrl}/api/test-token?user_id=${currentUser.id}`);
      if (tokenResp.ok) {
        const tokenData = await tokenResp.json();
        newToken = tokenData.token || '';
        setBearerToken(newToken);
      }
    } catch (e) {
      // If live backend cannot be reached, automatically fall back to demo mode gracefully
      setIsDemoMode(true);
      setPosts(DEMO_POSTS);
      setLoading(false);
      return;
    }

    fetchFeed(1, true, newToken);
  };

  const fetchFeed = async (pageNum: number, reset: boolean = false, overrideToken?: string) => {
    if (isDemoMode) {
      if (reset) {
        setPosts(DEMO_POSTS);
        setPage(1);
        setHasMore(true);
      } else if (pageNum === 2) {
        // Simulate infinite scroll page 2 in demo mode
        const page2 = DEMO_POSTS.map(p => ({
          ...p,
          id: p.id + 100,
          authenticity_score: Math.max(0.8, p.authenticity_score - 0.2),
          time_ago: 'Earlier this week',
        }));
        setPosts(prev => [...prev, ...page2]);
        setHasMore(false);
      }
      setLoading(false);
      setRefreshing(false);
      return;
    }

    try {
      const headers: Record<string, string> = {
        'Content-Type': 'application/json',
      };
      const tokenToUse = overrideToken !== undefined ? overrideToken : bearerToken;
      if (tokenToUse) {
        headers['Authorization'] = `Bearer ${tokenToUse}`;
      }

      const resp = await fetch(`${apiUrl}/api/feed?page=${pageNum}`, { headers });
      if (!resp.ok) {
        throw new Error(`Server returned HTTP ${resp.status}`);
      }

      const result = await resp.json();
      if (result.success && Array.isArray(result.data)) {
        if (reset) {
          setPosts(result.data);
        } else {
          setPosts(prev => [...prev, ...result.data]);
        }
        setPage(pageNum);
        setHasMore(result.meta?.has_more ?? false);
        setError(null);
      } else {
        throw new Error('Invalid feed response format.');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to connect to Guised Up social engine.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleRefresh = useCallback(() => {
    setRefreshing(true);
    setSearchQuery('');
    setIsSearching(false);
    fetchFeed(1, true);
  }, [apiUrl, isDemoMode, bearerToken]);

  const handleLoadMore = () => {
    if (!loading && !refreshing && hasMore && !isSearching) {
      fetchFeed(page + 1, false);
    }
  };

  // Perform Natural Language Semantic Search
  const handleSearchSubmit = async () => {
    if (!searchQuery || searchQuery.trim() === '') {
      setIsSearching(false);
      return;
    }

    setIsSearching(true);
    setLoading(true);

    if (isDemoMode) {
      setTimeout(() => {
        const queryLower = searchQuery.toLowerCase().trim();
        const topics: [RegExp, string[]][] = [
          [/travel|wander|hike|triund|spiti|jaipur|varanasi|delhi|train/, ['travel', 'wander', 'hike', 'triund', 'spiti', 'jaipur', 'varanasi', 'delhi', 'train']],
          [/food|recipe|cook|chai|biryani|paneer|momo|baking|sourdough|street food/, ['food', 'recipe', 'cook', 'chai', 'biryani', 'paneer', 'momo', 'baking', 'sourdough']],
          [/startup|founder|funding|vc|invest|pitch|burnout|startup/, ['startup', 'founder', 'funding', 'vc', 'invest', 'pitch', 'burnout']],
          [/code|tech|deploy|open.source|neovim|refactor|legacy|pair.program/, ['code', 'tech', 'deploy', 'open source', 'neovim', 'refactor', 'legacy', 'pair program']],
          [/music|guitar|song|vinyl|open mic|guitar|acoustic|perform/, ['music', 'guitar', 'song', 'vinyl', 'open mic', 'acoustic', 'perform']],
          [/fitness|workout|marathon|gym|run|exercise|discipline/, ['fitness', 'workout', 'marathon', 'gym', 'run', 'exercise', 'discipline']],
          [/poetry|poem|write|writer|poet|collection|published/, ['poetry', 'poem', 'write', 'writer', 'poet', 'collection', 'published']],
          [/gaming|stream|valorant|gamer|pc|twitch/, ['gaming', 'stream', 'valorant', 'gamer', 'pc', 'twitch']],
          [/book|read|library|novel|reading/, ['book', 'read', 'library', 'novel', 'reading']],
          [/yoga|wellness|meditation|gratitude|mindfulness/, ['yoga', 'wellness', 'meditation', 'gratitude', 'mindfulness']],
          [/fashion|design|outfit|saree|thrift|diwali/, ['fashion', 'design', 'outfit', 'saree', 'thrift', 'diwali']],
          [/journalism|podcast|interview|story|history|freedom/, ['journalism', 'podcast', 'interview', 'story', 'history', 'freedom']],
          [/love|relationship|friend|friend|connection|lonely/, ['love', 'relationship', 'friend', 'connection', 'lonely']],
          [/coffee|tea|morning|breeze|quiet/, ['coffee', 'tea', 'morning', 'breeze', 'quiet']],
        ];

        const matchedTopic = topics.find(([regex]) => regex.test(queryLower));
        const keywords = matchedTopic ? matchedTopic[1] : null;

        const filtered = DEMO_POSTS.map(p => {
          const contentLower = p.content.toLowerCase();
          let score = 0.45;
          if (contentLower.includes(queryLower)) {
            score = 0.95;
          } else if (keywords) {
            const matches = keywords.filter(k => contentLower.includes(k)).length;
            if (matches > 0) {
              score = 0.65 + (matches / keywords.length) * 0.30;
            }
          }
          return { ...p, similarity_score: Math.min(0.99, Math.round(score * 100) / 100) };
        }).sort((a, b) => (b.similarity_score || 0) - (a.similarity_score || 0));

        setSearchResults(filtered);
        setLoading(false);
      }, 350);
      return;
    }

    try {
      const headers: Record<string, string> = {};
      if (bearerToken) headers['Authorization'] = `Bearer ${bearerToken}`;

      const resp = await fetch(`${apiUrl}/api/search?q=${encodeURIComponent(searchQuery)}`, { headers });
      if (resp.ok) {
        const result = await resp.json();
        if (result.success && Array.isArray(result.data)) {
          setSearchResults(result.data);
          setError(null);
        }
      }
    } catch (err: any) {
      setError('Semantic search connection failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleClearSearch = () => {
    setSearchQuery('');
    setIsSearching(false);
    setSearchResults([]);
  };

  const handleReactionToggle = async (postId: number) => {
    if (isDemoMode) return;
    try {
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (bearerToken) headers['Authorization'] = `Bearer ${bearerToken}`;
      await fetch(`${apiUrl}/api/interactions`, {
        method: 'POST',
        headers,
        body: JSON.stringify({ post_id: postId, interaction_type: 'reaction' }),
      });
    } catch (e) {
      // Ignore background reaction logging error during offline testing
    }
  };

  const displayedPosts = isSearching ? searchResults : posts;

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle="light-content" backgroundColor="#0A0D14" />

      {/* Top Header & Search Bar */}
      <Header
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
        onSearchSubmit={handleSearchSubmit}
        onClearSearch={handleClearSearch}
        onOpenConfig={() => setConfigVisible(true)}
        isDemoMode={isDemoMode}
      />

      {/* Mode / Query Banner */}
      <View style={styles.bannerRow}>
        <Text style={styles.bannerText}>
          {isSearching
            ? `🧠 Semantic Vector Matches for "${searchQuery}"`
            : `✨ Personalized Feed for ${currentUser.name} (${isDemoMode ? 'Demo Mode' : 'Live Engine'})`}
        </Text>
      </View>

      {/* Main Content Area */}
      {loading && !refreshing ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#E6683B" />
          <Text style={styles.loadingText}>
            {isSearching ? 'Computing 1536-d cosine similarity...' : 'Ranking authentic connections...'}
          </Text>
        </View>
      ) : error ? (
        <View style={styles.errorBox}>
          <Text style={styles.errorIcon}>⚠️</Text>
          <Text style={styles.errorTitle}>Connection Error</Text>
          <Text style={styles.errorMessage}>{error}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={() => fetchFeed(1, true)}>
            <Text style={styles.retryText}>Retry Connection</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.demoSwitchButton} onPress={() => setIsDemoMode(true)}>
            <Text style={styles.demoSwitchText}>Switch to Demo Graph</Text>
          </TouchableOpacity>
        </View>
      ) : displayedPosts.length === 0 ? (
        <View style={styles.emptyBox}>
          <Text style={styles.emptyIcon}>🌱</Text>
          <Text style={styles.emptyTitle}>No Real Connections Yet</Text>
          <Text style={styles.emptyMessage}>
            {isSearching
              ? 'No posts matched your natural language query. Try searching for emotions, places, or moments.'
              : 'Your authentic feed is clean. Interact with creators to train your neural ranking graph!'}
          </Text>
        </View>
      ) : (
        <FlatList
          data={displayedPosts}
          keyExtractor={(item, idx) => `${item.id}-${idx}`}
          renderItem={({ item }) => (
            <PostCard
              post={item}
              onReact={handleReactionToggle}
              isSearchMode={isSearching}
            />
          )}
          contentContainerStyle={styles.listContainer}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              tintColor="#E6683B"
              colors={['#E6683B']}
            />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.3}
          ListFooterComponent={
            !isSearching && hasMore && posts.length > 0 ? (
              <View style={styles.footerLoader}>
                <ActivityIndicator size="small" color="#94A3B8" />
                <Text style={styles.footerLoaderText}>Loading more authentic connections...</Text>
              </View>
            ) : (
              <View style={styles.endOfFeed}>
                <Text style={styles.endOfFeedText}>✦ You are caught up with real connections ✦</Text>
              </View>
            )
          }
        />
      )}

      {/* Configuration Drawer */}
      <ConfigModal
        visible={configVisible}
        onClose={() => setConfigVisible(false)}
        apiUrl={apiUrl}
        setApiUrl={setApiUrl}
        isDemoMode={isDemoMode}
        setIsDemoMode={setIsDemoMode}
        currentUser={currentUser}
        setCurrentUser={setCurrentUser}
        onReload={() => fetchFeed(1, true)}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#0A0D14',
  },
  bannerRow: {
    backgroundColor: '#10151F',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#1C2434',
  },
  bannerText: {
    color: '#94A3B8',
    fontSize: 13,
    fontWeight: '700',
  },
  listContainer: {
    paddingTop: 16,
    paddingBottom: 40,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 30,
  },
  loadingText: {
    color: '#CBD5E1',
    marginTop: 16,
    fontSize: 15,
    fontWeight: '600',
  },
  errorBox: {
    margin: 24,
    padding: 24,
    backgroundColor: '#161922',
    borderRadius: 20,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#3F2525',
  },
  errorIcon: {
    fontSize: 40,
    marginBottom: 12,
  },
  errorTitle: {
    color: '#F8FAFC',
    fontSize: 18,
    fontWeight: '800',
    marginBottom: 8,
  },
  errorMessage: {
    color: '#94A3B8',
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: 20,
  },
  retryButton: {
    backgroundColor: '#E6683B',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
    marginBottom: 10,
    width: '100%',
    alignItems: 'center',
  },
  retryText: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 15,
  },
  demoSwitchButton: {
    backgroundColor: '#20293A',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
    width: '100%',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#33415A',
  },
  demoSwitchText: {
    color: '#CBD5E1',
    fontWeight: '700',
    fontSize: 15,
  },
  emptyBox: {
    margin: 24,
    padding: 30,
    backgroundColor: '#131822',
    borderRadius: 20,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#202836',
  },
  emptyIcon: {
    fontSize: 44,
    marginBottom: 14,
  },
  emptyTitle: {
    color: '#F8FAFC',
    fontSize: 18,
    fontWeight: '800',
    marginBottom: 8,
  },
  emptyMessage: {
    color: '#94A3B8',
    textAlign: 'center',
    lineHeight: 22,
  },
  footerLoader: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 20,
  },
  footerLoaderText: {
    color: '#94A3B8',
    fontSize: 13,
    marginLeft: 10,
  },
  endOfFeed: {
    alignItems: 'center',
    paddingVertical: 24,
  },
  endOfFeedText: {
    color: '#475569',
    fontSize: 13,
    fontWeight: '600',
  },
});
