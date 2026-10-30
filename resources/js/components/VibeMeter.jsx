import React, { useState, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import Card from './ui/Card';

const VIBE_LEVELS = [
    { key: 'sepi', icon: 'ph-leaf', label: 'Sepi', color: '#10b981', bg: 'from-emerald-500/20 to-emerald-600/10' },
    { key: 'lumayan', icon: 'ph-coffee', label: 'Lumayan', color: '#f59e0b', bg: 'from-amber-500/20 to-amber-600/10' },
    { key: 'rame', icon: 'ph-users-three', label: 'Rame', color: '#f97316', bg: 'from-orange-500/20 to-orange-600/10' },
    { key: 'penuh', icon: 'ph-fire', label: 'Penuh', color: '#ef4444', bg: 'from-red-500/20 to-red-600/10' },
];

const VibeMeter = ({ cafeId, cafeLat, cafeLng }) => {
    const [aggregate, setAggregate] = useState(null);
    const [hasVoted, setHasVoted] = useState(false);
    const [selectedLevel, setSelectedLevel] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);

    const getFingerprint = () => {
        let fp = localStorage.getItem('wadah-visitor-id');
        if (!fp) {
            fp = 'v-' + Math.random().toString(36).substr(2, 9) + Date.now();
            localStorage.setItem('wadah-visitor-id', fp);
        }
        return fp;
    };

    const fetchVibe = useCallback(async () => {
        try {
            const res = await fetch(`/cafes/${cafeId}/vibe`);
            if (res.ok) {
                const data = await res.json();
                setAggregate(data);
            }
        } catch (e) { /* silent */ }
    }, [cafeId]);

    useEffect(() => {
        fetchVibe();
        // Auto-refresh every 2 minutes
        const interval = setInterval(fetchVibe, 120000);
        return () => clearInterval(interval);
    }, [fetchVibe]);

    const submitVibe = async (level) => {
        if (isSubmitting || hasVoted) return;
        setSelectedLevel(level);
        setIsSubmitting(true);

        try {
            const fp = getFingerprint();
            const body = { level, fingerprint: fp };

            if (navigator.geolocation) {
                try {
                    const pos = await new Promise((resolve, reject) =>
                        navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 5000 })
                    );
                    body.user_lat = pos.coords.latitude;
                    body.user_lng = pos.coords.longitude;
                } catch (e) { /* GPS optional */ }
            }

            const res = await fetch(`/cafes/${cafeId}/vibe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(body),
            });

            const data = await res.json();

            if (res.ok) {
                setAggregate(data.aggregate);
                setHasVoted(true);
                setShowSuccess(true);
                if (window.hapticFeedback) window.hapticFeedback('medium');
                setTimeout(() => setShowSuccess(false), 3000);
            } else {
                setHasVoted(true); // Already voted
            }
        } catch (e) {
            console.error('[VibeMeter] Error:', e);
        } finally {
            setIsSubmitting(false);
        }
    };

    const total = aggregate?.total || 0;
    const dominant = aggregate?.dominant;
    const dominantInfo = VIBE_LEVELS.find(v => v.key === dominant);

    return (
        <section className="mb-12">
            <div className="flex items-center justify-between mb-5 px-1">
                <h2 className="text-sm font-bold text-espresso uppercase tracking-widest flex items-center gap-2.5">
                    <i className="ph-fill ph-pulse text-amber-600 text-lg"></i>
                    Live Vibe
                </h2>
                {aggregate?.last_updated && (
                    <span className="text-[0.6rem] font-bold text-slate-400 uppercase tracking-wider">
                        {aggregate.last_updated}
                    </span>
                )}
            </div>

            <Card variant="premium" className="text-white shadow-2xl">
                {/* Background Decor */}
                <div className="absolute top-0 right-0 w-40 h-40 bg-amber-500/5 rounded-full blur-3xl -mr-20 -mt-20"></div>
                <div className="absolute bottom-0 left-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl -ml-16 -mb-16"></div>

                {/* Dominant Vibe Display */}
                {total > 0 && dominantInfo ? (
                    <div className="relative z-10 mb-5 flex items-center gap-4">
                        <motion.div
                            className="text-5xl"
                            animate={{ scale: [1, 1.1, 1] }}
                            transition={{ repeat: Infinity, duration: 2 }}
                        >
                            <i className={`ph-fill ${dominantInfo.icon}`} aria-hidden="true"></i>
                        </motion.div>
                        <div>
                            <p className="text-[0.65rem] font-bold text-white/40 uppercase tracking-widest">Status Sekarang</p>
                            <p className="text-xl font-black" style={{ color: dominantInfo.color }}>
                                {dominantInfo.label}
                            </p>
                            <p className="text-[0.7rem] text-white/50 font-medium">{total} warga update</p>
                        </div>
                    </div>
                ) : (
                    <div className="relative z-10 mb-5">
                        <p className="text-white/60 text-sm font-medium">Belum ada yang lapor vibe hari ini.</p>
                        <p className="text-white/40 text-[0.7rem]">Jadilah yang pertama untuk memberi tahu pengunjung lain.</p>
                    </div>
                )}

                {/* Distribution Bars */}
                {total > 0 && (
                    <div className="relative z-10 space-y-2 mb-5" role="group" aria-label="Statistik distribusi vibe">
                        {VIBE_LEVELS.map(v => {
                            const count = aggregate?.distribution?.[v.key] || 0;
                            const pct = total > 0 ? (count / total) * 100 : 0;
                            return (
                                <div key={v.key} className="flex items-center gap-3">
                                    <span className="text-base w-6 text-center"><i className={`ph-fill ${v.icon}`} aria-hidden="true"></i></span>
                                    <span className="text-[0.6rem] font-bold text-white/50 w-16 uppercase tracking-wider">{v.label}</span>
                                    <div
                                        className="flex-1 h-2 bg-white/5 rounded-full overflow-hidden"
                                        role="progressbar"
                                        aria-valuenow={Math.round(pct)}
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-label={`Persentase vibe ${v.label}`}
                                    >
                                        <motion.div
                                            className="h-full rounded-full"
                                            style={{ backgroundColor: v.color }}
                                            initial={{ width: 0 }}
                                            animate={{ width: `${pct}%` }}
                                            transition={{ duration: 0.8, ease: "easeOut" }}
                                        />
                                    </div>
                                    <span className="text-[0.6rem] font-bold text-white/40 w-6 text-right">{count}</span>
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Vote Buttons */}
                {!hasVoted ? (
                    <div className="relative z-10 grid grid-cols-4 gap-2" role="group" aria-label="Pilihan lapor vibe">
                        {VIBE_LEVELS.map(v => (
                            <motion.button
                                key={v.key}
                                aria-label={`Pilih vibe ${v.label}`}
                                aria-pressed={selectedLevel === v.key}
                                tabIndex={0}
                                onClick={() => submitVibe(v.key)}
                                disabled={isSubmitting}
                                whileTap={{ scale: 0.92 }}
                                className={`flex flex-col items-center gap-1.5 py-3 px-2 rounded-2xl border transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 focus-visible:ring-offset-espresso ${
                                    selectedLevel === v.key
                                        ? 'border-amber-500/50 bg-white/10'
                                        : 'glass-card hover:bg-white/10 active:bg-white/15' /* pake class glass-card biar kodenya ga kepanjangan cuy */
                                } ${isSubmitting ? 'opacity-50' : ''}`}
                            >
                                <i className={`ph-fill ${v.icon} text-2xl`} aria-hidden="true"></i>
                                <span className="text-[0.55rem] font-bold uppercase tracking-wider text-white/70">{v.label}</span>
                            </motion.button>
                        ))}
                    </div>
                ) : (
                    <AnimatePresence>
                        {showSuccess && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0 }}
                                className="relative z-10 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-3"
                            >
                                <i className="ph-fill ph-check-circle text-emerald-400 text-xl"></i>
                                <span className="text-emerald-400 text-xs font-bold">Vibe tercatat! Data update otomatis tiap 2 menit.</span>
                            </motion.div>
                        )}
                    </AnimatePresence>
                )}
            </Card>
        </section>
    );
};

export default VibeMeter;
