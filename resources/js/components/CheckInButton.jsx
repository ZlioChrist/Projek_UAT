import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';

const CheckInButton = ({ cafeId, cafeName, cafeLat, cafeLng, isAuthenticated }) => {
    const [isCheckedIn, setIsCheckedIn] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [isVerified, setIsVerified] = useState(false);
    const [newBadges, setNewBadges] = useState([]);
    const [showBadgePopup, setShowBadgePopup] = useState(false);

    const handleCheckIn = async () => {
        if (isCheckedIn || isLoading) return;

        if (!isAuthenticated || isAuthenticated === 'false') {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { message: 'Login dulu untuk check-in!', type: 'warning' }
            }));
            return;
        }

        setIsLoading(true);

        try {
            const body = {};

            if (navigator.geolocation) {
                try {
                    const pos = await new Promise((resolve, reject) =>
                        navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 5000 })
                    );
                    body.user_lat = pos.coords.latitude;
                    body.user_lng = pos.coords.longitude;
                } catch (e) { /* GPS optional */ }
            }

            const res = await fetch(`/cafes/${cafeId}/check-in`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(body),
            });

            const data = await res.json();

            if (res.ok) {
                setIsCheckedIn(true);
                setIsVerified(data.is_verified);
                if (window.hapticFeedback) window.hapticFeedback('medium');

                if (data.new_badges && data.new_badges.length > 0) {
                    setNewBadges(data.new_badges);
                    setTimeout(() => setShowBadgePopup(true), 800);
                }

                let toastMessage = 'Check-in berhasil.';
                if (data.is_verified) {
                    toastMessage = 'Lokasi GPS berhasil diverifikasi.';
                } else if (data.verification_reason === 'too_far') {
                    toastMessage = 'Jarak GPS cukup jauh, tetapi check-in tetap tercatat.';
                } else if (data.verification_reason === 'no_gps') {
                    toastMessage = 'Check-in manual tanpa GPS berhasil.';
                }

                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: toastMessage,
                        type: 'success'
                    }
                }));
            } else {
                if (res.status === 401) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'Login dulu untuk check-in!', type: 'warning' }
                    }));
                } else {
                    setIsCheckedIn(true); // Already checked in today
                }
            }
        } catch (e) {
            console.error('[CheckIn] Error:', e);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <>
            <section className="mb-6">
                <motion.button
                    onClick={handleCheckIn}
                    disabled={isCheckedIn || isLoading}
                    whileTap={{ scale: 0.96 }}
                    aria-live="polite" /* biar screen reader tau pas lagi loading ngab */
                    className={`w-full flex items-center justify-center gap-3 py-4 rounded-2xl font-bold text-sm transition-all shadow-lg ${
                        isCheckedIn
                            ? 'bg-emerald-50 border-2 border-emerald-200 text-emerald-600'
                            : 'bg-gradient-to-r from-amber-500 to-orange-500 text-white border-2 border-amber-400/50 hover:shadow-xl active:shadow-md'
                    }`}
                >
                    {isLoading ? (
                        <>
                            <motion.i
                                className="ph-bold ph-circle-notch text-lg"
                                animate={{ rotate: 360 }}
                                transition={{ repeat: Infinity, duration: 1, ease: "linear" }}
                            />
                            Memverifikasi lokasi...
                        </>
                    ) : isCheckedIn ? (
                        <>
                            <i className="ph-fill ph-check-circle text-lg"></i>
                            Sudah Check-in Hari Ini
                            {isVerified && <span className="inline-flex items-center gap-1 text-[0.6rem] bg-emerald-100 px-2 py-0.5 rounded-full ml-1"><i className="ph-fill ph-check-circle" aria-hidden="true"></i>GPS</span>}
                        </>
                    ) : (
                        <>
                            <i className="ph-fill ph-map-pin-area text-lg"></i>
                            Check-in di {cafeName}
                        </>
                    )}
                </motion.button>
            </section>

            {/* New Badge Popup */}
            <AnimatePresence>
                {showBadgePopup && newBadges.length > 0 && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-[15000] bg-black/70 backdrop-blur-sm flex items-center justify-center p-6"
                        onClick={() => setShowBadgePopup(false)}
                        role="status" /* tambah role status biar SR otomatis baca pas popup muncul ngab */
                        aria-live="polite"
                    >
                        <motion.div
                            initial={{ scale: 0.5, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            exit={{ scale: 0.5, opacity: 0 }}
                            transition={{ type: "spring", damping: 15 }}
                            className="bg-white rounded-[32px] p-8 max-w-sm w-full text-center shadow-2xl"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <motion.div
                                animate={{ rotate: [0, -10, 10, -5, 5, 0], scale: [1, 1.2, 1] }}
                                transition={{ duration: 0.8 }}
                                className="text-6xl mb-4 text-amber-500"
                            >
                                <i className="ph-fill ph-trophy" aria-hidden="true"></i>
                            </motion.div>
                            <motion.h3
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.1 }}
                                className="text-2xl font-black text-[#2C1810] mb-2"
                            >
                                Badge Baru!
                            </motion.h3>
                            <motion.p
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.2 }}
                                className="text-slate-500 text-sm mb-6"
                            >
                                Lo baru aja unlock achievement baru!
                            </motion.p>
                            <div className="space-y-3">
                                {newBadges.map((slug, i) => (
                                    <motion.div
                                        key={i}
                                        initial={{ opacity: 0, y: 10 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        transition={{ delay: 0.3 + (i * 0.1) }}
                                        className="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3"
                                    >
                                        <i className="ph-fill ph-medal text-2xl text-amber-600" aria-hidden="true"></i>
                                        <span className="font-bold text-[#2C1810] text-sm">{slug.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                                    </motion.div>
                                ))}
                            </div>
                            <motion.button
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.5 }}
                                onClick={() => setShowBadgePopup(false)}
                                className="mt-6 w-full py-3 bg-[#1A0F0A] text-white rounded-2xl font-bold text-sm hover:scale-[1.02] active:scale-95 transition-all"
                            >
                                Mantap!
                            </motion.button>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
};

export default CheckInButton;
