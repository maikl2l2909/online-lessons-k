import { Link, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';

export function CheckoutSuccessPage() {
  const [params] = useSearchParams();
  const sessionId = params.get('session_id');

  const { data, isLoading } = useQuery({
    queryKey: ['checkout-verify', sessionId],
    queryFn: async () => (await api.post('/checkout/verify', { session_id: sessionId })).data,
    enabled: !!sessionId,
  });

  if (isLoading) {
    return <div className="p-8 text-center text-slate-400">Verifying payment...</div>;
  }

  return (
    <div className="mx-auto max-w-lg px-4 py-20 text-center">
      <div className="text-5xl">🎉</div>
      <h1 className="mt-6 text-2xl font-bold text-white">Payment successful!</h1>
      <p className="mt-4 text-slate-400">
        You now have access to <strong className="text-white">{data?.course?.title}</strong>
      </p>
      <Link
        to={`/courses/${data?.course?.slug}`}
        className="mt-8 inline-block rounded-xl bg-indigo-600 px-6 py-3 font-medium hover:bg-indigo-500"
      >
        Start learning
      </Link>
    </div>
  );
}
