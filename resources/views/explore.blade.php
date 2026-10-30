@extends('layouts.app')

@section('title', 'Jelajahi Cafe Terbaik di Kalimantan - WadahNgopi')
@section('meta_description', 'Temukan cafe terdekat, paling hits, dan nyaman untuk nugas atau nongkrong di Samarinda, Balikpapan, dan sekitarnya. Filter berdasarkan fasilitas, jam buka, dan suasana.')
@section('og_title', 'Jelajahi Cafe Terbaik - WadahNgopi')
@section('og_description', 'Cari cafe favoritmu di Kalimantan dengan mudah. Lengkap dengan info fasilitas, jam buka, dan menu.')
@section('og_image', asset('wadahngopi.png'))

@section('content')
    <livewire:explore-search />
@endsection