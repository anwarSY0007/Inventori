import { BreadcrumbItem } from "@/types";
import { Head } from "@inertiajs/react";

interface CategoryProps
{
    breadcrumbs:BreadcrumbItem,
    title:string,
    href:string
}

export default function CategoryPage({title}:CategoryProps) {
  return (
    <h1>
    <Head title={title}/>
    </h1>
  );
}
