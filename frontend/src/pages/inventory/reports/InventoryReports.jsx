// src/pages/inventory/reports/InventoryReports.jsx
import React from 'react';
import { Link } from 'react-router-dom';
import { BarChart2, TrendingUp, FileText, DollarSign } from 'lucide-react';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardBody } from '../../../components/common/Card';

const InventoryReports = () => {
  const reports = [
    {
      title: 'Stock Status Report',
      description: 'View current inventory levels with status indicators',
      icon: BarChart2,
      color: 'bg-blue-100 text-blue-600',
      path: '/inventory/reports/stock'
    },
    {
      title: 'Inventory Movement Report',
      description: 'Track stock inflows and outflows over time',
      icon: TrendingUp,
      color: 'bg-green-100 text-green-600',
      path: '/inventory/reports/movement'
    },
    {
      title: 'Inventory Adjustments Report',
      description: 'View all stock adjustments and their details',
      icon: FileText,
      color: 'bg-purple-100 text-purple-600',
      path: '/inventory/reports/adjustment'
    },
    {
      title: 'Inventory Valuation Report',
      description: 'Get the financial value of your current inventory',
      icon: DollarSign,
      color: 'bg-amber-100 text-amber-600',
      path: '/inventory/reports/valuation'
    }
  ];

  return (
    <div>
      <PageHeader 
        title="Inventory Reports" 
        subtitle="Access and generate reports to analyze your inventory data"
      />
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {reports.map((report, index) => (
          <Link key={index} to={report.path} className="block">
            <Card className="h-full hover:shadow-md transition-shadow duration-200">
              <CardBody>
                <div className="flex items-start">
                  <div className={`p-3 rounded-full ${report.color} mr-4`}>
                    <report.icon className="h-6 w-6" />
                  </div>
                  <div>
                    <h3 className="text-lg font-medium text-gray-900">{report.title}</h3>
                    <p className="mt-1 text-sm text-gray-500">{report.description}</p>
                  </div>
                </div>
              </CardBody>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
};

export default InventoryReports;

