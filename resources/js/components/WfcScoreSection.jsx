import React, { useState, useEffect } from 'react';
import { motion } from 'motion/react';
import WfcModal from './WfcModal';

const WfcScoreSection = ({ cafeId, cafeName, initialScore, initialCount, cafeLat, cafeLng, hasRated: initialHasRated }) => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [score, setScore] = useState(initialScore || 0);
    const [count, setCount] = useState(initialCount || 0);
    const [hasRated, setHasRated] = useState(initialHasRated === 'true');
    const [showContextualTrigger, setShowContextualTrigger] = useState(false);

    useEffect(() => {
        // Contextual Trigger: Only show if user hasn't rated yet
        if (!hasRated && navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((pos) => {
                const dist = calculateDistance(pos.coords.latitude, pos.coords.longitude, cafeLat, cafeLng);
                if (dist <= 100) { // If within 100m, show contextual trigger
                    setShowContextualTrigger(true);
                }
            }, null, { enableHighAccuracy: true });
        }
    }, []);

    const calculateDistance = (lat1, lng1, lat2, lng2) => {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    };

    const handleSuccess = (newAggregate) => {
        if (newAggregate) {
            setScore(newAggregate.score);
            setCount(newAggregate.count);
            setHasRated(true);
        }
    };

    return (
        <section className="mb-12">
            <div className="flex items-center justify-between mb-5 px-1">
                <h2 className="text-sm font-bold text-espresso uppercase tracking-widest flex items-center gap-2.5">
                    <i className="ph-fill ph-laptop text-amber-600 text-lg"></i>
                    Vibe Produktivitas
                </h2>
                
                {count > 0 && (
                    <span className="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 px-2 py-1 rounded-lg">
                        {count} Verifikasi Warga
                    </span>
                )}
            </div>

            <div className="bg-gradient-to-br from-[#1A0F0A] to-[#2D1B12] rounded-[32px] p-6 text-white relative overflow-hidden shadow-2xl">
                {/* Background Decor */}
                <div className="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full blur-3xl -mr-16 -mt-16"></div>

                <div className="relative z-10 flex items-center gap-6">
                    {/* Score Gauge */}
                    <div className="flex flex-col items-center">
                        <div className="relative w-20 h-20 flex items-center justify-center">
                            <svg className="w-full h-full -rotate-90">
                                <circle 
                                    cx="40" cy="40" r="36" 
                                    className="stroke-white/10 fill-none" 
                                    strokeWidth="6"
                                />
                                <motion.circle 
                                    cx="40" cy="40" r="36" 
                                    className="stroke-amber-500 fill-none" 
                                    strokeWidth="6"
                                    strokeDasharray="226"
                                    initial={{ strokeDashoffset: 226 }}
                                    animate={{ strokeDashoffset: 226 - (226 * (score / 5)) }}
                                    transition={{ duration: 1.5, ease: "easeOut" }}
                                    strokeLinecap="round"
                                />
                            </svg>
                            <span className="absolute text-2xl font-black">{score > 0 ? score : '-'}</span>
                        </div>
                        <span className="text-[0.6rem] font-bold text-amber-500/60 uppercase tracking-widest mt-2">SKOR WFC</span>
                    </div>

                    <div className="flex-1">
                        <p className="text-white/60 text-[0.8rem] font-medium leading-relaxed mb-4">
                            {count > 0 
                                ? `WiFi kenceng, meja luas, gak bakal diusir walau cuma pesen satu latte. Skor: ${score}/5.` 
                                : "Belum ada yang validasi vibe di sini. Jadilah yang pertama kasih tau warga lainnya!"}
                        </p>
                        
                        <button 
                            onClick={() => !hasRated && setIsModalOpen(true)}
                            disabled={hasRated}
                            className={`inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all ${hasRated ? 'bg-green-500/10 text-green-500 border border-green-500/20 opacity-80' : 'bg-white/10 hover:bg-white/20 border border-white/10 active:scale-95'}`}
                        >
                            <i className={`ph ${hasRated ? 'ph-check-circle' : 'ph-plus-circle'}`}></i>
                            {hasRated ? 'Sudah Validasi' : 'Validasi Vibe'}
                        </button>
                    </div>
                </div>
            </div>

            {/* Contextual Trigger Popup */}
            {showContextualTrigger && (
                <motion.div 
                    initial={{ y: 50, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    className="fixed bottom-32 left-4 right-4 z-[5000]"
                >
                    <div className="bg-amber-500 p-4 rounded-2xl shadow-2xl flex items-center justify-between gap-4 border-2 border-white/20">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-[#1A0F0A] rounded-xl flex items-center justify-center text-amber-500">
                                <i className="ph-fill ph-navigation-arrow text-xl"></i>
                            </div>
                            <div>
                                <p className="text-[#1A0F0A] font-black text-sm">Lagi di {cafeName}?</p>
                                <p className="text-[#1A0F0A]/60 text-[0.65rem] font-bold uppercase">Kasih tau warga lain spot ini asik buat nugas atau nggak!</p>
                            </div>
                        </div>
                        <button 
                            onClick={() => { setShowContextualTrigger(false); setIsModalOpen(true); }}
                            className="bg-[#1A0F0A] text-white px-4 py-2 rounded-xl text-xs font-black shadow-lg"
                        >
                            GAS
                        </button>
                    </div>
                </motion.div>
            )}

            <WfcModal 
                cafeId={cafeId}
                cafeName={cafeName}
                cafeLat={cafeLat}
                cafeLng={cafeLng}
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSuccess={handleSuccess}
            />
        </section>
    );
};

export default WfcScoreSection;
