import React from 'react';
import { Modal, View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView } from 'react-native';

interface ConfigModalProps {
  visible: boolean;
  onClose: () => void;
  apiUrl: string;
  setApiUrl: (url: string) => void;
  isDemoMode: boolean;
  setIsDemoMode: (val: boolean) => void;
  currentUser: { id: number; name: string };
  setCurrentUser: (user: { id: number; name: string }) => void;
  onReload: () => void;
}

const TEST_USERS = [
  { id: 1, name: 'Aarav Sharma' },
  { id: 2, name: 'Diya Patel' },
  { id: 3, name: 'Rohan Gupta' },
  { id: 4, name: 'Maya Lin' },
  { id: 5, name: 'Vikram Singh' },
  { id: 6, name: 'Priya Kapoor' },
  { id: 7, name: 'Arjun Mehta' },
  { id: 8, name: 'Sara Ali' },
  { id: 9, name: 'Karan Joshi' },
  { id: 10, name: 'Ananya Reddy' },
  { id: 11, name: 'Rahul Verma' },
  { id: 12, name: 'Ishita Nair' },
  { id: 13, name: 'Dev Thakur' },
  { id: 14, name: 'Naina Pillai' },
  { id: 15, name: 'Kabir Bhatia' },
];

export const ConfigModal: React.FC<ConfigModalProps> = ({
  visible,
  onClose,
  apiUrl,
  setApiUrl,
  isDemoMode,
  setIsDemoMode,
  currentUser,
  setCurrentUser,
  onReload,
}) => {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.modalBox}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>⚡ Social Engine Settings</Text>
            <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
              <Text style={styles.closeText}>✕</Text>
            </TouchableOpacity>
          </View>

          <ScrollView style={styles.scrollArea}>
            {/* Mode Switch */}
            <Text style={styles.sectionTitle}>Data Source Mode</Text>
            <View style={styles.toggleRow}>
              <TouchableOpacity
                style={[styles.toggleBtn, !isDemoMode && styles.toggleBtnActive]}
                onPress={() => { setIsDemoMode(false); onReload(); }}
              >
                <Text style={[styles.toggleText, !isDemoMode && styles.toggleTextActive]}>
                  🚀 Live Laravel API
                </Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.toggleBtn, isDemoMode && styles.toggleBtnActive]}
                onPress={() => { setIsDemoMode(true); onReload(); }}
              >
                <Text style={[styles.toggleText, isDemoMode && styles.toggleTextActive]}>
                  🛡️ Built-in Demo Data
                </Text>
              </TouchableOpacity>
            </View>

            {/* API URL Setting */}
            {!isDemoMode && (
              <>
                <Text style={styles.sectionTitle}>Backend API Base URL</Text>
                <TextInput
                  style={styles.input}
                  value={apiUrl}
                  onChangeText={setApiUrl}
                  placeholder="e.g. http://10.0.2.2:8000"
                  placeholderTextColor="#64748B"
                />
                <Text style={styles.hintText}>
                  Use http://10.0.2.2:8000 for Android emulator or http://localhost:8000 for web/iOS simulator.
                </Text>
              </>
            )}

            {/* Switch Active User Context */}
            <Text style={styles.sectionTitle}>Active User Graph Context</Text>
            <View style={styles.usersRow}>
              {TEST_USERS.map(u => (
                <TouchableOpacity
                  key={u.id}
                  style={[styles.userChip, currentUser.id === u.id && styles.userChipActive]}
                  onPress={() => { setCurrentUser(u); }}
                >
                  <Text style={[styles.userChipText, currentUser.id === u.id && styles.userChipTextActive]}>
                    👤 {u.name.split(' ')[0]}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
            <Text style={styles.hintText}>
              Switching user alters the Relationship Depth ranking signals in real time!
            </Text>
          </ScrollView>

          <TouchableOpacity style={styles.saveBtn} onPress={() => { onReload(); onClose(); }}>
            <Text style={styles.saveBtnText}>Apply & Refresh Feed</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.75)',
    justifyContent: 'flex-end',
  },
  modalBox: {
    backgroundColor: '#121721',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 20,
    maxHeight: '80%',
    borderWidth: 1,
    borderColor: '#242E42',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#1F293A',
  },
  modalTitle: {
    color: '#F8FAFC',
    fontSize: 18,
    fontWeight: '800',
  },
  closeBtn: {
    padding: 4,
  },
  closeText: {
    color: '#94A3B8',
    fontSize: 18,
  },
  scrollArea: {
    marginBottom: 16,
  },
  sectionTitle: {
    color: '#CBD5E1',
    fontSize: 14,
    fontWeight: '700',
    marginTop: 14,
    marginBottom: 8,
  },
  toggleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  toggleBtn: {
    flex: 1,
    backgroundColor: '#19202E',
    paddingVertical: 12,
    borderRadius: 12,
    alignItems: 'center',
    marginHorizontal: 4,
    borderWidth: 1,
    borderColor: '#263147',
  },
  toggleBtnActive: {
    backgroundColor: 'rgba(230, 104, 59, 0.2)',
    borderColor: '#E6683B',
  },
  toggleText: {
    color: '#94A3B8',
    fontWeight: '600',
    fontSize: 13,
  },
  toggleTextActive: {
    color: '#E6683B',
    fontWeight: '700',
  },
  input: {
    backgroundColor: '#19202E',
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    color: '#F8FAFC',
    fontSize: 14,
    borderWidth: 1,
    borderColor: '#2A364F',
  },
  hintText: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 6,
    lineHeight: 16,
  },
  usersRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  userChip: {
    backgroundColor: '#19202E',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 20,
    marginRight: 8,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#263147',
  },
  userChipActive: {
    backgroundColor: '#E6683B',
    borderColor: '#E6683B',
  },
  userChipText: {
    color: '#CBD5E1',
    fontWeight: '600',
  },
  userChipTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  saveBtn: {
    backgroundColor: '#E6683B',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  saveBtnText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 16,
  },
});
