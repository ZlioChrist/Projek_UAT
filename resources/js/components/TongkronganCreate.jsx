import React, { useState, useEffect, useRef } from 'react';

const TongkronganCreate = () => {
    const [title, setTitle] = useState('');
    const [search, setSearch] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [selectedCafes, setSelectedCafes] = useState([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [shareUrl, setShareUrl] = useState('');

    const searchDebounceRef = useRef(null);

    // Effect for search with debounce
    useEffect(() => {
        if (search.length < 2) {
            setSearchResults([]);
            return;
        }

        if (searchDebounceRef.current) {
            clearTimeout(searchDebounceRef.current);
        }

        searchDebounceRef.current = setTimeout(async () => {
            try {
                const selectedIds = selectedCafes.map((c) => c.id).join('&selected[]=');
                const queryParam = `?q=${encodeURIComponent(search)}${selectedCafes.length > 0 ? '&selected[]=' + selectedIds : ''}`;
                const res = await fetch(`/tongkrongan/search-cafes${queryParam}`);
                if (res.ok) {
                    const data = await res.json();
                    setSearchResults(data);
                }
            } catch (err) {
                console.error('Gagal cari cafe ngab', err);
            }
        }, 300);

        return () => clearTimeout(searchDebounceRef.current);
    }, [search, selectedCafes]);

    const addCafe = (cafe) => {
        if (selectedCafes.length >= 5) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Maksimal 5 cafe per list!', type: 'warning' } }));
            return;
        }
        if (!selectedCafes.find((c) => c.id === cafe.id)) {
            setSelectedCafes([...selectedCafes, cafe]);
        }
        setSearch('');
        setSearchResults([]);
    };

    const removeCafe = (cafeId) => {
        setSelectedCafes(selectedCafes.filter((c) => c.id !== cafeId));
    };

    const submitTongkrongan = async () => {
        if (selectedCafes.length < 2) return;

        setIsSubmitting(true);
        try {
            const res = await fetch('/tongkrongan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    title,
                    cafe_ids: selectedCafes.map(c => c.id),
                    // Fingerprint udah di-handle di backend pake session!
                }),
            });

            const data = await res.json();
            if (res.ok) {
                setShareUrl(data.share_url);
                if (window.hapticFeedback) window.hapticFeedback('medium');
            } else {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Gagal membuat list.', type: 'error' } }));
            }
        } catch (e) {
            console.error('Error bikin tongkrongan ngab', e);
        } finally {
            setIsSubmitting(false);
        }
    };

    const copyLink = () => {
        navigator.clipboard.writeText(shareUrl).then(() => {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Link disalin!', type: 'success' } }));
        });
    };

    return (
        <div className="space-y-6">
            {/* Title Input */}
            <div>
                <label className="block text-xs font-bold text-espresso-dark uppercase tracking-widest mb-2">
                    <i className="ph-fill ph-pen-nib text-amber-600"></i> Judul Tongkrongan
                </label>
                <input
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    type="text"
                    maxLength={100}
                    placeholder="Contoh: Opsi Jumat Malam, Cafe Buat Nugas Bareng..."
                    className="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white text-sm font-medium text-espresso-dark placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all"
                />
            </div>

            {/* Cafe Search */}
            <div className="relative">
                <label className="block text-xs font-bold text-espresso-dark uppercase tracking-widest mb-2">
                    <i className="ph-fill ph-magnifying-glass text-amber-600"></i> Cari & Pilih Cafe (Min 2, Max 5)
                </label>
                <input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    type="text"
                    placeholder="Ketik nama cafe..."
                    className="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white text-sm font-medium text-espresso-dark placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all"
                />

                {/* Search Results Dropdown */}
                {searchResults.length > 0 && (
                    <div className="absolute z-10 w-full mt-2 bg-white rounded-2xl border border-slate-100 shadow-lg overflow-hidden divide-y divide-slate-50">
                        {searchResults.map((cafe) => (
                            <button
                                key={cafe.id}
                                onClick={() => addCafe(cafe)}
                                className="w-full flex items-center gap-3 p-3 hover:bg-amber-50 transition-all text-left"
                            >
                                <div className="w-10 h-10 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0">
                                    {cafe.image_path ? (
                                        <img src={`/storage/${cafe.image_path}`} loading="lazy" className="w-full h-full object-cover" alt="" />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-slate-300">
                                            <i className="ph-fill ph-coffee text-lg"></i>
                                        </div>
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <p className="text-sm font-bold text-espresso-dark truncate">{cafe.name}</p>
                                    <p className="text-[0.6rem] text-slate-400 truncate">{cafe.address}</p>
                                </div>
                                <i className="ph-bold ph-plus-circle text-amber-500 text-lg ml-auto flex-shrink-0"></i>
                            </button>
                        ))}
                    </div>
                )}
            </div>

            {/* Selected Cafes */}
            {selectedCafes.length > 0 && (
                <div className="space-y-2">
                    <p className="text-xs font-bold text-slate-400 uppercase tracking-widest">{selectedCafes.length}/5 Cafe Dipilih</p>
                    {selectedCafes.map((cafe, i) => (
                        <div key={cafe.id} className="flex items-center gap-3 bg-white border border-slate-100 p-3 rounded-2xl shadow-sm">
                            <span className="w-7 h-7 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 text-xs font-black flex-shrink-0">
                                {i + 1}
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-bold text-espresso-dark truncate">{cafe.name}</p>
                            </div>
                            <button
                                onClick={() => removeCafe(cafe.id)}
                                className="w-8 h-8 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center hover:bg-rose-100 active:scale-90 transition-all flex-shrink-0"
                            >
                                <i className="ph-bold ph-x text-sm"></i>
                            </button>
                        </div>
                    ))}
                </div>
            )}

            {/* Submit Button */}
            <button
                onClick={submitTongkrongan}
                disabled={isSubmitting || selectedCafes.length < 2 || !title}
                className={`w-full py-4 rounded-2xl font-bold text-sm transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed ${
                    isSubmitting || selectedCafes.length < 2 || !title
                        ? 'bg-slate-200 text-slate-400'
                        : 'bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:shadow-xl active:scale-[0.98]'
                }`}
            >
                {!isSubmitting ? (
                    <span className="flex items-center justify-center gap-2">
                        <i className="ph-fill ph-share-network"></i>
                        Buat & Share ke Grup!
                    </span>
                ) : (
                    <span>Membuat...</span>
                )}
            </button>

            {/* Share Result */}
            {shareUrl && (
                <div className="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 text-center animate-fade-in">
                    <i className="ph-fill ph-check-circle text-emerald-500 text-3xl mb-2"></i>
                    <p className="text-sm font-bold text-emerald-700 mb-3">Tongkrongan berhasil dibuat!</p>
                    <div className="flex gap-2">
                        <button
                            onClick={copyLink}
                            className="flex-1 py-3 bg-white border border-emerald-200 rounded-xl text-xs font-bold text-emerald-600 active:scale-95 transition-all"
                        >
                            <i className="ph-bold ph-copy"></i> Salin Link
                        </button>
                        <a
                            href={`https://wa.me/?text=${encodeURIComponent('Vote cafe buat tongkrongan! ' + shareUrl)}`}
                            target="_blank"
                            rel="noreferrer"
                            className="flex-1 flex items-center justify-center gap-1 py-3 bg-emerald-500 rounded-xl text-xs font-bold text-white active:scale-95 transition-all no-underline text-center"
                        >
                            <i className="ph-fill ph-whatsapp-logo"></i> Share WA
                        </a>
                    </div>
                </div>
            )}
        </div>
    );
};

export default TongkronganCreate;
