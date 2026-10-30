import React, { useState, useEffect } from 'react';

const TongkronganView = ({ tongkrongan }) => {
    // Helper to calculate max votes and sorted items based on current vote counts
    const getSortedItems = (currentVoteCounts) => {
        const itemsWithVotes = tongkrongan.items.map(item => ({
            ...item,
            currentVoteCount: currentVoteCounts[item.id] !== undefined
                ? currentVoteCounts[item.id]
                : item.votes.length
        }));

        const sorted = itemsWithVotes.sort((a, b) => b.currentVoteCount - a.currentVoteCount);
        const maxVotes = sorted.length > 0 ? sorted[0].currentVoteCount : 0;

        return { sorted, maxVotes };
    };

    // State for vote counts. Key is item.id, value is vote count.
    const [voteCounts, setVoteCounts] = useState(() => {
        const initial = {};
        tongkrongan.items.forEach(item => {
            initial[item.id] = item.votes.length;
        });
        return initial;
    });

    const { sorted: items, maxVotes } = getSortedItems(voteCounts);

    // State for which items this user has voted for
    const [votedItems, setVotedItems] = useState(() => {
        try {
            const saved = localStorage.getItem(`tongkrongan-votes-${tongkrongan.uuid}`);
            return saved ? JSON.parse(saved) : [];
        } catch (e) {
            return [];
        }
    });

    // Polling effect every 10 seconds
    useEffect(() => {
        const fetchVotes = async () => {
            try {
                const res = await fetch(`/tongkrongan/${tongkrongan.uuid}/votes`);
                if (res.ok) {
                    const data = await res.json();
                    setVoteCounts(data.votes);
                }
            } catch (e) {
                console.error('Gagal auto-refresh vote ngab', e);
            }
        };

        const interval = setInterval(fetchVotes, 10000);
        return () => clearInterval(interval);
    }, [tongkrongan.uuid]);

    const handleVote = async (itemId) => {
        try {
            const res = await fetch(`/tongkrongan/${tongkrongan.uuid}/vote/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
                // Fingerprint is handled securely by backend session
            });

            const data = await res.json();
            if (res.ok) {
                let newVotedItems = [...votedItems];
                if (data.action === 'added') {
                    if (!newVotedItems.includes(itemId)) newVotedItems.push(itemId);
                } else {
                    newVotedItems = newVotedItems.filter(id => id !== itemId);
                }

                setVotedItems(newVotedItems);
                localStorage.setItem(`tongkrongan-votes-${tongkrongan.uuid}`, JSON.stringify(newVotedItems));

                setVoteCounts(prev => ({
                    ...prev,
                    [itemId]: data.vote_count
                }));

                if (window.hapticFeedback) window.hapticFeedback('light');
            } else {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'error' } }));
            }
        } catch (e) {
            console.error('[Vote]', e);
        }
    };

    const copyLink = (url) => {
        navigator.clipboard.writeText(url).then(() => {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Link disalin!', type: 'success' } }));
        });
    };

    return (
        <div>
            {/* Title & Timer */}
            <div className="mb-6">
                <h2 className="text-2xl font-black text-[#2C1810] leading-tight">{tongkrongan.title}</h2>
                <div className="flex items-center gap-2 mt-2">
                    <span className="text-[0.65rem] font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg flex items-center gap-1">
                        <i className="ph-fill ph-timer"></i>
                        {/* We use a simple message since the exact countdown was passed via blade in livewire. In a real app we'd calculate this properly. */}
                        Live Voting
                    </span>
                    <span className="text-[0.65rem] font-bold text-slate-400">
                        {Object.values(voteCounts).reduce((a, b) => a + b, 0)} votes total
                    </span>
                </div>
            </div>

            {/* Cafe Cards with Vote */}
            <div className="space-y-3">
                {items.map((item) => {
                    const cafe = item.cafe;
                    const voteCount = item.currentVoteCount;
                    const isWinner = maxVotes > 0 && voteCount === maxVotes;
                    const pct = maxVotes > 0 ? (voteCount / maxVotes) * 100 : 0;
                    const hasVoted = votedItems.includes(item.id);

                    return (
                        <div key={item.id} className={`relative bg-white border ${isWinner && voteCount > 0 ? 'border-amber-300 ring-2 ring-amber-100' : 'border-slate-100'} rounded-[24px] p-4 shadow-sm transition-all hover:shadow-md`}>
                            {/* Winner Crown */}
                            {isWinner && voteCount > 0 && (
                                <div className="absolute -top-3 left-4 bg-amber-500 text-white text-[0.5rem] font-black uppercase tracking-widest px-2.5 py-1 rounded-full shadow-md">
                                    <i className="ph-fill ph-crown mr-1" aria-hidden="true"></i> Teratas
                                </div>
                            )}

                            <div className="flex items-center gap-3">
                                {/* Cafe Image */}
                                <div className="w-14 h-14 rounded-2xl overflow-hidden bg-slate-100 flex-shrink-0">
                                    {cafe && cafe.image_path ? (
                                        <img src={`/storage/${cafe.image_path}`} loading="lazy" className="w-full h-full object-cover" alt={cafe.name} />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-slate-300">
                                            <i className="ph-fill ph-coffee text-2xl"></i>
                                        </div>
                                    )}
                                </div>

                                {/* Cafe Info */}
                                <div className="flex-1 min-w-0">
                                    <h3 className="text-sm font-black text-[#2C1810] truncate">{cafe?.name || 'Cafe'}</h3>
                                    <p className="text-[0.6rem] text-slate-400 font-medium truncate">
                                        <i className="ph-fill ph-map-pin text-amber-500"></i>
                                        {cafe?.address ? (cafe.address.length > 40 ? cafe.address.substring(0, 40) + '...' : cafe.address) : ''}
                                    </p>
                                    {/* Vote Bar */}
                                    <div className="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className={`h-full rounded-full transition-all duration-700 ${isWinner && voteCount > 0 ? 'bg-amber-500' : 'bg-slate-300'}`}
                                            style={{ width: `${pct}%` }}
                                        ></div>
                                    </div>
                                </div>

                                {/* Vote Button */}
                                <button
                                    onClick={() => handleVote(item.id)}
                                    aria-label={`Vote untuk ${cafe?.name || 'Cafe'}`} /* tambah aria-label buat aksesibilitas ngab */
                                    className={`flex flex-col items-center justify-center w-14 h-14 rounded-2xl border-2 transition-all active:scale-90 flex-shrink-0 ${hasVoted ? 'bg-amber-500 text-white border-amber-400' : 'bg-white text-[#2C1810] border-slate-200 hover:border-amber-300'}`}
                                >
                                    <i className={`${hasVoted ? 'ph-fill ph-heart' : 'ph ph-heart'} text-lg`}></i>
                                    <span className="text-[0.55rem] font-black mt-0.5">{voteCount}</span>
                                </button>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Share Section */}
            <div className="mt-8 bg-gradient-to-br from-[#1A0F0A] to-[#2D1B12] rounded-[28px] p-6 text-white text-center">
                <p className="text-white/60 text-xs font-medium mb-3">Share biar temen lo ikut vote!</p>
                <div className="flex gap-2">
                    <button
                        onClick={() => copyLink(tongkrongan.share_url)}
                        className="flex-1 py-3 bg-white/10 border border-white/10 rounded-xl text-xs font-bold text-white active:scale-95 transition-all"
                    >
                        <i className="ph-bold ph-copy"></i> Salin Link
                    </button>
                    <a
                        href={`https://wa.me/?text=${encodeURIComponent('Vote cafe buat tongkrongan! ' + tongkrongan.share_url)}`}
                        target="_blank"
                        rel="noreferrer"
                        className="flex-1 flex items-center justify-center gap-1 py-3 bg-emerald-500 rounded-xl text-xs font-bold text-white active:scale-95 transition-all no-underline text-center"
                    >
                        <i className="ph-fill ph-whatsapp-logo"></i> Share WA
                    </a>
                </div>
            </div>
        </div>
    );
};

export default TongkronganView;
