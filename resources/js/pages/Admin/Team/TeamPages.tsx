import { DataTable } from '@/components/data-table/data-table';
import { DataTableColumnHeader } from '@/components/data-table/data-table-column-header';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Mail, MoreHorizontal, Pencil, Phone, Shield, Trash2, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface TeamMember {
  id: string;
  name: string;
  email: string;
  phone?: string;
  avatar?: string;
  role: string;
  role_label: string;
  current_teams?: {
    name: string;
  };
  created_at: string;
  joined_at: string;
}

interface Props {
  members: TeamMember[];
  available_roles: Array<{ value: string; label: string }>;
  can: {
    create_member: boolean;
    delete_member: boolean;
  };
}

export default function TeamMembersIndex({ members, can }: Props) {
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [memberToDelete, setMemberToDelete] = useState<TeamMember | null>(null);

  const handleDelete = (member: TeamMember) => {
    setMemberToDelete(member);
    setDeleteDialogOpen(true);
  };

  const confirmDelete = () => {
    if (!memberToDelete) return;

    router.delete(`/team/members/${memberToDelete.id}`, {
      onSuccess: () => {
        toast.success('Member deleted successfully');
        setDeleteDialogOpen(false);
        setMemberToDelete(null);
      },
      onError: (errors) => {
        toast.error(errors.message || 'Failed to delete member');
      },
    });
  };

  const getRoleBadgeVariant = (role: string) => {
    switch (role) {
      case 'admin_merchant':
        return 'default';
      case 'cashier':
        return 'secondary';
      case 'warehouse_staff':
        return 'outline';
      default:
        return 'outline';
    }
  };

  const columns: ColumnDef<TeamMember>[] = [
    {
      id: 'select',
      header: ({ table }) => (
        <Checkbox
          checked={table.getIsAllPageRowsSelected()}
          onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
          aria-label='Select all'
          className='translate-y-0.5'
        />
      ),
      cell: ({ row }) => (
        <Checkbox
          checked={row.getIsSelected()}
          onCheckedChange={(value) => row.toggleSelected(!!value)}
          aria-label='Select row'
          className='translate-y-0.5'
        />
      ),
      enableSorting: false,
      enableHiding: false,
    },
    {
      accessorKey: 'name',
      header: ({ column }) => <DataTableColumnHeader column={column} title='Member' />,
      cell: ({ row }) => {
        const member = row.original;
        return (
          <div className='flex items-center gap-3'>
            <Avatar className='h-10 w-10'>
              <AvatarImage src={member.avatar} alt={member.name} />
              <AvatarFallback>
                {member.name
                  .split(' ')
                  .map((n) => n[0])
                  .join('')
                  .toUpperCase()}
              </AvatarFallback>
            </Avatar>
            <div className='flex flex-col'>
              <span className='font-medium'>{member.name}</span>
              <div className='flex items-center gap-2 text-sm text-muted-foreground'>
                <Mail className='h-3 w-3' />
                {member.email}
              </div>
              {member.phone && (
                <div className='flex items-center gap-2 text-sm text-muted-foreground'>
                  <Phone className='h-3 w-3' />
                  {member.phone}
                </div>
              )}
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: 'role_label',
      header: ({ column }) => <DataTableColumnHeader column={column} title='Role' />,
      cell: ({ row }) => {
        const member = row.original;
        return (
          <Badge variant={getRoleBadgeVariant(member.role)} className='gap-1'>
            <Shield className='h-3 w-3' />
            {member.role_label}
          </Badge>
        );
      },
      filterFn: (row, _id, value) => {
        return value.includes(row.original.role);
      },
    },
    {
      accessorKey: 'current_teams.name',
      header: 'Team',
      cell: ({ row }) => {
        return <span className='text-sm'>{row.original.current_teams?.name || '-'}</span>;
      },
    },
    {
      accessorKey: 'joined_at',
      header: ({ column }) => <DataTableColumnHeader column={column} title='Joined' />,
      cell: ({ row }) => {
        return (
          <div className='flex flex-col'>
            <span className='text-sm font-medium'>{row.getValue('joined_at')}</span>
            <span className='text-xs text-muted-foreground'>
              Created: {row.original.created_at}
            </span>
          </div>
        );
      },
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const member = row.original;

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant='ghost' className='h-8 w-8 p-0'>
                <span className='sr-only'>Open menu</span>
                <MoreHorizontal className='h-4 w-4' />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align='end'>
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
              <DropdownMenuItem onClick={() => navigator.clipboard.writeText(member.email)}>
                Copy email
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => router.visit(`/team/members/${member.id}/edit`)}>
                <Pencil className='mr-2 h-4 w-4' />
                Edit role
              </DropdownMenuItem>
              {can.delete_member && (
                <DropdownMenuItem
                  className='text-destructive focus:text-destructive'
                  onClick={() => handleDelete(member)}
                >
                  <Trash2 className='mr-2 h-4 w-4' />
                  Remove member
                </DropdownMenuItem>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  // Custom toolbar with role filter
  const toolbar = (
    <div className='flex items-center gap-2'>
      {can.create_member && (
        <Button onClick={() => router.visit('/team/members/create')} size='sm' className='h-8'>
          <UserPlus className='mr-2 h-4 w-4' />
          Add Member
        </Button>
      )}
    </div>
  );

  return (
    <>
      <Head title='Team Members' />

      <div className='container mx-auto py-10'>
        {/* Header */}
        <div className='mb-8'>
          <h1 className='text-3xl font-bold tracking-tight'>Team Members</h1>
          <p className='text-muted-foreground'>Manage your team members and assign roles</p>
        </div>

        {/* Stats Cards (Optional) */}
        <div className='mb-8 grid gap-4 md:grid-cols-3'>
          <div className='rounded-lg border p-4'>
            <div className='text-sm font-medium text-muted-foreground'>Total Members</div>
            <div className='text-2xl font-bold'>{members.length}</div>
          </div>
          <div className='rounded-lg border p-4'>
            <div className='text-sm font-medium text-muted-foreground'>Admins</div>
            <div className='text-2xl font-bold'>
              {members.filter((m) => m.role === 'admin_merchant').length}
            </div>
          </div>
          <div className='rounded-lg border p-4'>
            <div className='text-sm font-medium text-muted-foreground'>Cashiers</div>
            <div className='text-2xl font-bold'>
              {members.filter((m) => m.role === 'cashier').length}
            </div>
          </div>
        </div>

        {/* DataTable */}
        <DataTable
          columns={columns}
          data={members}
          searchKey='name'
          searchPlaceholder='Search members...'
          toolbar={toolbar}
        />
      </div>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
            <AlertDialogDescription>
              This will remove <strong>{memberToDelete?.name}</strong> from your team. They will
              lose access to all team resources.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={confirmDelete}
              className='bg-destructive hover:bg-destructive/90'
            >
              Remove Member
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
