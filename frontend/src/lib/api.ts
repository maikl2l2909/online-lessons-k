import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || '/api/v1';

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'student' | 'admin';
}

export interface Video {
  id: number;
  status: string;
  duration_seconds: number | null;
  thumbnail_url: string | null;
  stream_url: string | null;
}

export interface Lesson {
  id: number;
  title: string;
  description: string | null;
  order: number;
  is_free_preview: boolean;
  can_view: boolean;
  duration_seconds?: number | null;
  video?: Video;
  progress?: { completed: boolean; watched_seconds: number } | null;
}

export interface Section {
  id: number;
  title: string;
  order: number;
  lessons: Lesson[];
}

export interface Course {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  price_cents: number;
  formatted_price: string;
  currency: string;
  thumbnail: string | null;
  status: string;
  sections?: Section[];
  lessons_count?: number;
  is_purchased?: boolean;
}
