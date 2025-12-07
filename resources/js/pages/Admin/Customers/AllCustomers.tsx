import { Customer } from '@/types';
import { useEffect } from 'react';

interface PageProps {
  customers: Customer[];

  // filters: {
  //   search?: string;
  //   role?: string;
  // };
}

export default function AllCustomers({ customers }: PageProps) {
  useEffect(() => {
    console.table(customers);
  }, [customers]);
  return <div>AllCustomers</div>;
}
