import React, { useState } from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity, Animated } from 'react-native';

export interface Author {
  id: number;
  name: string;
  avatar_url?: string;
}

export interface PostItem {
  id: number;
  author_id: number;
  author: Author;
  content: string;
  image_url?: string | null;
  authenticity_score: number;
  view_count: number;
  reaction_count: number;
  has_reacted: boolean;
  created_at: string;
  time_ago: string;
  similarity_score?: number;
}

interface PostCardProps {
  post: PostItem;
  onReact: (postId: number) => void;
  isSearchMode?: boolean;
}

export const PostCard: React.FC<PostCardProps> = ({ post, onReact, isSearchMode = false }) => {
  const [reacted, setReacted] = useState(post.has_reacted);
  const [reactionCount, setReactionCount] = useState(post.reaction_count || 0);
  const scaleAnim = useState(new Animated.Value(1))[0];

  const handlePressReaction = () => {
    Animated.sequence([
      Animated.timing(scaleAnim, { toValue: 1.35, duration: 120, useNativeDriver: true }),
      Animated.timing(scaleAnim, { toValue: 1.0, duration: 120, useNativeDriver: true }),
    ]).start();

    if (!reacted) {
      setReacted(true);
      setReactionCount(prev => prev + 1);
    } else {
      setReacted(false);
      setReactionCount(prev => Math.max(0, prev - 1));
    }
    onReact(post.id);
  };

  // Format authenticity score badge (e.g. 1.85x Authentic)
  const authFormatted = `${(post.authenticity_score || 1.0).toFixed(2)}x`;
  const isHighAuth = post.authenticity_score >= 1.7;

  // Format avatar fallback initials
  const initials = post.author.name
    ? post.author.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
    : 'GU';

  return (
    <View style={styles.card}>
      {/* Top Header Row: Avatar, Name, Time, Authenticity & Similarity Badges */}
      <View style={styles.headerRow}>
        <View style={styles.authorSection}>
          {post.author.avatar_url ? (
            <Image
              source={{ uri: post.author.avatar_url }}
              style={styles.avatar}
              resizeMode="cover"
            />
          ) : (
            <View style={styles.avatarFallback}>
              <Text style={styles.avatarText}>{initials}</Text>
            </View>
          )}
          <View style={styles.nameContainer}>
            <View style={styles.nameRow}>
              <Text style={styles.authorName}>{post.author.name}</Text>
              <View style={styles.dotSeparator} />
              <Text style={styles.timeAgo}>{post.time_ago}</Text>
            </View>
            <Text style={styles.handleText}>@{post.author.name.toLowerCase().replace(/\s+/g, '')}</Text>
          </View>
        </View>

        {/* Badges Column */}
        <View style={styles.badgeColumn}>
          {isSearchMode && post.similarity_score !== undefined && (
            <View style={styles.similarityBadge}>
              <Text style={styles.similarityText}>
                🎯 {(post.similarity_score * 100).toFixed(0)}% Match
              </Text>
            </View>
          )}
          <View style={[styles.authBadge, isHighAuth ? styles.authBadgeHigh : styles.authBadgeNormal]}>
            <Text style={[styles.authText, isHighAuth && styles.authTextHigh]}>
              ⚡ {authFormatted} Raw
            </Text>
          </View>
        </View>
      </View>

      {/* Post Text Content */}
      <Text style={styles.content}>{post.content}</Text>

      {/* Optional Raw Image Content */}
      {post.image_url ? (
        <Image
          source={{ uri: post.image_url }}
          style={styles.postImage}
          resizeMode="cover"
        />
      ) : null}

      {/* Footer / Reaction Button Row */}
      <View style={styles.footerRow}>
        <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
          <TouchableOpacity
            style={[styles.reactionButton, reacted && styles.reactionButtonActive]}
            onPress={handlePressReaction}
            activeOpacity={0.8}
          >
            <Text style={styles.reactionEmoji}>{reacted ? '🧡' : '🤍'}</Text>
            <Text style={[styles.reactionCountText, reacted && styles.reactionCountTextActive]}>
              {reactionCount > 0 ? reactionCount : 'Connect'}
            </Text>
          </TouchableOpacity>
        </Animated.View>

        <View style={styles.metricsGroup}>
          <Text style={styles.metricsText}>👁️ {post.view_count || 1} views</Text>
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#131822',
    borderRadius: 18,
    padding: 18,
    marginHorizontal: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#202836',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.35,
    shadowRadius: 8,
    elevation: 6,
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 12,
  },
  authorSection: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 1.5,
    borderColor: '#E6683B',
    backgroundColor: '#1E2533',
  },
  avatarFallback: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#232D3F',
    borderWidth: 1.5,
    borderColor: '#E6683B',
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    color: '#F47B4E',
    fontWeight: '700',
    fontSize: 16,
  },
  nameContainer: {
    marginLeft: 12,
    flex: 1,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  authorName: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 15,
  },
  dotSeparator: {
    width: 4,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#64748B',
    marginHorizontal: 6,
  },
  timeAgo: {
    color: '#94A3B8',
    fontSize: 12,
  },
  handleText: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 2,
  },
  badgeColumn: {
    alignItems: 'flex-end',
  },
  similarityBadge: {
    backgroundColor: '#1E3A5F',
    borderRadius: 10,
    paddingHorizontal: 8,
    paddingVertical: 3,
    marginBottom: 4,
    borderWidth: 1,
    borderColor: '#3B82F6',
  },
  similarityText: {
    color: '#60A5FA',
    fontSize: 11,
    fontWeight: '700',
  },
  authBadge: {
    borderRadius: 10,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderWidth: 1,
  },
  authBadgeHigh: {
    backgroundColor: 'rgba(230, 104, 59, 0.15)',
    borderColor: '#E6683B',
  },
  authBadgeNormal: {
    backgroundColor: 'rgba(100, 116, 139, 0.15)',
    borderColor: '#475569',
  },
  authText: {
    color: '#94A3B8',
    fontSize: 11,
    fontWeight: '600',
  },
  authTextHigh: {
    color: '#F47B4E',
    fontWeight: '700',
  },
  content: {
    color: '#E2E8F0',
    fontSize: 15,
    lineHeight: 22,
    marginBottom: 14,
  },
  postImage: {
    width: '100%',
    height: 220,
    borderRadius: 12,
    marginBottom: 14,
    backgroundColor: '#1E2533',
  },
  footerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#1E2636',
  },
  reactionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#1D2534',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#2D3748',
  },
  reactionButtonActive: {
    backgroundColor: 'rgba(230, 104, 59, 0.2)',
    borderColor: '#E6683B',
  },
  reactionEmoji: {
    fontSize: 14,
    marginRight: 6,
  },
  reactionCountText: {
    color: '#CBD5E1',
    fontWeight: '600',
    fontSize: 13,
  },
  reactionCountTextActive: {
    color: '#F47B4E',
  },
  metricsGroup: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  metricsText: {
    color: '#64748B',
    fontSize: 12,
  },
});
