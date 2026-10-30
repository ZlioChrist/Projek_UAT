import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'motion/react';

const WfcModal = ({ cafeId, cafeName, cafeLat, cafeLng, isOpen, onClose, onSuccess }) => {
    const [step, setStep] = useState('input'); // input, loading, success
    const [ratings, setRatings] = useState({
        wifi_rating: 0,
        outlet_rating: 0,
        comfort_rating: 0,
    });
    const [comment, setComment] = useState('');
    const [isVerified, setIsVerified] = useState(false);
    const [verificationReason, setVerificationReason] = useState('no_gps');
    const [error, setError] = useState(null);
    const [shareImage, setShareImage] = useState(null);

    const checkLocation = () => {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject("Geolocation tidak didukung browser lu.");
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    });
                },
                (err) => {
                    reject("Gagal ambil lokasi. Pastikan GPS aktif.");
                },
                { enableHighAccuracy: true }
            );
        });
    };

    const handleSubmit = async () => {
        if (ratings.wifi_rating === 0 || ratings.outlet_rating === 0 || ratings.comfort_rating === 0) {
            setError("Isi semua rating dulu dong!");
            return;
        }

        setStep('loading');
        setError(null);

        try {
            const location = await checkLocation().catch(() => null);
            
            const response = await fetch(`/cafes/${cafeId}/wfc-score`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    ...ratings,
                    comment,
                    user_lat: location?.lat,
                    user_lng: location?.lng,
                }),
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();
            
            if (response.ok) {
                setIsVerified(result.is_verified);
                setVerificationReason(result.verification_reason || 'no_gps');
                
                // Langsung nembak success state biar UI ga kerasa ngelag
                setStep('success');
                if (onSuccess) onSuccess(result.new_aggregate);
                
                // Kerjain canvas-nya di background secara async
                generateShareCardAsync(result.score, result.is_verified);
            } else {
                setError(result.message || "Gagal kirim score.");
                setStep('input');
            }
        } catch (err) {
            setError("Koneksi bermasalah.");
            setStep('input');
        }
    };

    // Lempar ke async event loop biar UI main thread ga freeze pas bikin gambar cuy
    const generateShareCardAsync = (scoreData, verified) => {
        requestAnimationFrame(() => {
            setTimeout(() => {
                const canvas = document.createElement('canvas');
        canvas.width = 1080;
        canvas.height = 1920;
        const ctx = canvas.getContext('2d');

        // 1. PREMIUM BACKGROUND (Deep Espresso Gradient)
        const bgGrad = ctx.createLinearGradient(0, 0, 1080, 1920);
        bgGrad.addColorStop(0, '#120A07');
        bgGrad.addColorStop(0.5, '#1A0F0A');
        bgGrad.addColorStop(1, '#0F0805');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, 1080, 1920);

        // 2. AMBIENT MESH ORBS (Subtle Glows)
        const drawOrb = (x, y, radius, color) => {
            const orbGrad = ctx.createRadialGradient(x, y, 0, x, y, radius);
            orbGrad.addColorStop(0, color);
            orbGrad.addColorStop(1, 'transparent');
            ctx.fillStyle = orbGrad;
            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fill();
        };
        drawOrb(100, 400, 800, 'rgba(234, 179, 8, 0.08)');
        drawOrb(980, 1500, 900, 'rgba(251, 191, 36, 0.05)');

        // 3. MAIN GLASS CARD CONTAINER
        const cardX = 100, cardY = 350, cardW = 880, cardH = 1250;
        ctx.save();
        ctx.shadowColor = 'rgba(0,0,0,0.5)';
        ctx.shadowBlur = 80;
        ctx.fillStyle = 'rgba(255, 255, 255, 0.03)';
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
        ctx.lineWidth = 2;
        // Draw rounded card
        const r = 60;
        ctx.beginPath();
        ctx.moveTo(cardX + r, cardY);
        ctx.arcTo(cardX + cardW, cardY, cardX + cardW, cardY + cardH, r);
        ctx.arcTo(cardX + cardW, cardY + cardH, cardX, cardY + cardH, r);
        ctx.arcTo(cardX, cardY + cardH, cardX, cardY, r);
        ctx.arcTo(cardX, cardY, cardX + cardW, cardY, r);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
        ctx.restore();

        // 4. HEADER: REPORT TYPE
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.letterSpacing = '10px';
        ctx.font = 'black 32px Inter, system-ui';
        ctx.fillStyle = 'rgba(251, 191, 36, 0.6)';
        ctx.fillText('WFC COMPATIBILITY REPORT', 540, 480);
        ctx.letterSpacing = '0px';

        // 5. CAFE NAME (Dynamic wrap if too long)
        ctx.fillStyle = '#FFFFFF';
        ctx.font = '900 86px Inter, system-ui';
        const displayTitle = cafeName.length > 20 ? cafeName.substring(0, 18) + '...' : cafeName;
        ctx.fillText(displayTitle, 540, 580);

        // 6. CENTRAL SCORE GAUGE
        const avg = ((scoreData.wifi_rating + scoreData.outlet_rating + scoreData.comfort_rating) / 3).toFixed(1);
        
        // Gauge Background Circle
        ctx.beginPath();
        ctx.arc(540, 920, 220, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(255,255,255,0.05)';
        ctx.lineWidth = 25;
        ctx.stroke();

        // Gauge Progress Arc
        ctx.beginPath();
        const startAngle = -Math.PI / 2;
        const endAngle = startAngle + (Math.PI * 2 * (avg / 5));
        ctx.arc(540, 920, 220, startAngle, endAngle);
        const gaugeGrad = ctx.createLinearGradient(540, 700, 540, 1140);
        gaugeGrad.addColorStop(0, '#F59E0B');
        gaugeGrad.addColorStop(1, '#D97706');
        ctx.strokeStyle = gaugeGrad;
        ctx.lineCap = 'round';
        ctx.lineWidth = 25;
        ctx.stroke();

        // Score Text
        ctx.fillStyle = '#FFFFFF';
        ctx.font = '900 160px Inter, system-ui';
        ctx.fillText(avg, 540, 910);
        ctx.font = '800 42px Inter, system-ui';
        ctx.fillStyle = 'rgba(255,255,255,0.4)';
        ctx.fillText('WFC SCORE', 540, 1010);

        // 7. VERIFIED BADGE
        if (verified) {
            const badgeY = 1180;
            ctx.fillStyle = 'rgba(16, 185, 129, 0.15)';
            ctx.roundRect(400, badgeY - 35, 280, 70, 35);
            ctx.fill();
            ctx.fillStyle = '#10B981';
            ctx.font = 'bold 28px Inter, system-ui';
            ctx.fillText('✓ VERIFIED BY GPS', 540, badgeY);
        }

        // 8. RATING ROWS (Bottom Section)
        const drawMetric = (label, value, y) => {
            ctx.textAlign = 'left';
            ctx.fillStyle = 'rgba(255,255,255,0.5)';
            ctx.font = '800 34px Inter, system-ui';
            ctx.fillText(label, 220, y);
            
            ctx.textAlign = 'right';
            const starSize = 38;
            for(let i=1; i<=5; i++) {
                ctx.fillStyle = i <= value ? '#F59E0B' : 'rgba(255,255,255,0.1)';
                ctx.font = `${starSize}px system-ui`;
                ctx.fillText('★', 860 - (5-i) * (starSize + 5), y);
            }
        };

        const metricsYStart = 1320;
        drawMetric('WiFi Speed', scoreData.wifi_rating, metricsYStart);
        drawMetric('Power Outlets', scoreData.outlet_rating, metricsYStart + 85);
        drawMetric('Vibe Comfort', scoreData.comfort_rating, metricsYStart + 170);

        // 9. FOOTER: BRANDING
        ctx.textAlign = 'center';
        ctx.fillStyle = 'rgba(255,255,255,0.2)';
        ctx.font = '900 32px Inter, system-ui';
        ctx.letterSpacing = '8px';
        ctx.fillText('WADAHNGOPI.COM', 540, 1800);

        // Logo Placement (Mock)
        ctx.fillStyle = '#F59E0B';
        ctx.beginPath(); ctx.arc(540, 200, 60, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = '#000';
        ctx.font = 'bold 40px system-ui';
        ctx.fillText('W', 540, 200);

        setShareImage(canvas.toDataURL('image/png', 0.9));
            }, 0);
        });
    };

    const handleShare = () => {
        if (!shareImage) return;
        const link = document.createElement('a');
        link.download = `WFC-Report-${cafeName}.png`;
        link.href = shareImage;
        link.click();
    };

    if (!isOpen) return null;

    return (
        <AnimatePresence>
            <motion.div 
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="fixed inset-0 z-[10000] flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-md p-4"
                onClick={onClose}
            >
                <motion.div 
                    initial={{ y: 100, scale: 0.9 }}
                    animate={{ y: 0, scale: 1 }}
                    exit={{ y: 100, scale: 0.9 }}
                    className="bg-espresso w-full max-w-md rounded-[32px] overflow-hidden shadow-2xl border border-white/10"
                    onClick={e => e.stopPropagation()}
                >
                    {step === 'input' && (
                        <div className="p-8">
                            <div className="flex justify-between items-center mb-6">
                                <div>
                                    <p className="text-white font-black text-sm">Lagi di {cafeName}?</p>
                                    <p className="text-white/60 text-[0.65rem] font-bold uppercase">Kasih tau warga lain spot ini asik buat nugas atau nggak!</p>
                                </div>
                                <button onClick={onClose} className="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-white/40 hover:text-white transition-colors">
                                    <i className="ph ph-x text-lg"></i>
                                </button>
                            </div>

                            {error && <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-bold rounded-xl">{error}</div>}

                            <div className="space-y-6 animate-fade-up delay-100">
                                <RatingRow label="Kestabilan WiFi" value={ratings.wifi_rating} onChange={v => setRatings({...ratings, wifi_rating: v})} />
                                <RatingRow label="Ketersediaan Colokan" value={ratings.outlet_rating} onChange={v => setRatings({...ratings, outlet_rating: v})} />
                                <RatingRow label="Vibe Produktivitas" value={ratings.comfort_rating} onChange={v => setRatings({...ratings, comfort_rating: v})} />
                                
                                <div>
                                    <label className="block text-white/40 text-[0.65rem] font-bold uppercase tracking-widest mb-2 px-1">Review Singkat (Wajib Jujur)</label>
                                    <textarea 
                                        className="w-full glass-card rounded-2xl p-4 text-white text-sm focus:outline-none focus:border-amber-500 transition-colors"
                                        placeholder="Spot paling pas buat ngegalau pas ujan, atau emang buat tempur tugas? Kasih tau warga lain!"
                                        rows="3"
                                        value={comment}
                                        onChange={e => setComment(e.target.value)}
                                    ></textarea>
                                </div>
                            </div>

                            <button 
                                onClick={handleSubmit}
                                className="w-full mt-8 py-4 bg-amber-500 hover:bg-amber-400 text-black font-black rounded-2xl transition-all active:scale-95 shadow-xl shadow-amber-500/20 animate-up delay-300"
                            >
                                KIRIM VALIDASI
                            </button>
                        </div>
                    )}

                    {step === 'loading' && (
                        <div className="p-12 text-center">
                            <div className="w-16 h-16 border-4 border-amber-500/20 border-t-amber-500 rounded-full animate-spin mx-auto mb-6"></div>
                            <h3 className="text-white text-lg font-black">Mengecek Lokasi...</h3>
                            <p className="text-white/40 text-sm mt-2">Pastikan lu beneran di lokasi biar terverifikasi.</p>
                        </div>
                    )}

                    {step === 'success' && (
                        <div className="p-8 text-center">
                            <div className="w-20 h-20 bg-amber-500/20 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-6 animate-up">
                                <i className="ph-fill ph-check-circle text-4xl"></i>
                            </div>
                            <h3 className="text-white text-2xl font-black animate-up delay-100">Berhasil Terkirim!</h3>
                            
                            {isVerified ? (
                                <p className="text-white/60 text-sm mt-2 mb-8 animate-up delay-200">
                                    Mantap! Skor lu terverifikasi lewat GPS.
                                </p>
                            ) : verificationReason === 'too_far' ? (
                                <p className="text-amber-500/80 text-sm mt-2 mb-8 animate-up delay-200">
                                    Skor terkirim! Jarak lumayan jauh (GPS mall suka nyasar), tapi gapapa brok tetep valid!
                                </p>
                            ) : (
                                <p className="text-white/60 text-sm mt-2 mb-8 animate-up delay-200">
                                    Skor terkirim secara manual tanpa GPS. Thanks udah nge-review!
                                </p>
                            )}

                            {shareImage && (
                                <div className="mb-8 rounded-2xl overflow-hidden border border-white/10 shadow-lg animate-up delay-300">
                                    <img src={shareImage} className="w-full aspect-[9/16] object-cover" alt="Share Card" />
                                </div>
                            )}

                            <div className="flex gap-3 animate-up delay-400">
                                <button onClick={handleShare} className="flex-1 py-4 bg-white/10 text-white font-black rounded-2xl hover:bg-white/20 transition-all">
                                    DOWNLOAD KARTU
                                </button>
                                <button onClick={onClose} className="flex-1 py-4 bg-amber-500 text-espresso font-black rounded-2xl hover:bg-amber-400 transition-all">
                                    SELESAI
                                </button>
                            </div>
                        </div>
                    )}
                </motion.div>
            </motion.div>
        </AnimatePresence>
    );
};

const RatingRow = ({ label, value, onChange }) => {
    return (
        <div className="flex justify-between items-center">
            <span className="text-white/60 text-sm font-bold">{label}</span>
            <div className="flex gap-2">
                {[1, 2, 3, 4, 5].map(star => (
                    <button 
                        key={star}
                        onClick={() => onChange(star)}
                        className={`w-8 h-8 rounded-lg flex items-center justify-center transition-all ${star <= value ? 'bg-amber-500 text-espresso' : 'bg-white/5 text-white/20'}`}
                    >
                        <i className={`ph-fill ph-star text-sm`}></i>
                    </button>
                ))}
            </div>
        </div>
    );
};

export default WfcModal;
