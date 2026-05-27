import { __, sprintf } from "@wordpress/i18n";
import { useState, useEffect, useMemo, useRef } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import InfoBox from "./InfoBox";
import "./LoginRedirects.css";

// Add this CSS to your admin stylesheet or in a style tag
const styles = ``;
const loginpressEnableLLMS = window.loginpressEnableLLMS || false;

const LoginRedirects = () => {
  const activeAddons = window.loginpressProData?.activeAddons || [];

  if (!activeAddons.includes('login-redirects')) {
    return;
  }
  const [loading, setLoading] = useState(true);
  const [loadingButton, setLoadingButton] = useState(null); // Track which button is loading: { type: 'update' | 'delete', id: string, entityType: 'user' | 'role' | 'llms' }
  const [activeTab, setActiveTab] = useState("users");
  const [users, setUsers] = useState([]);
  const [allRoles, setAllRoles] = useState([]); // All available roles
  const [roles, setRoles] = useState([]);
  const [perPage, setPerPage] = useState(10);
  const [message, setMessage] = useState(null);
  const [searchTerm, setSearchTerm] = useState({
    users: "",
    roles: "",
    llms: ""
  });
  const [showHint, setShowHint] = useState(false);
  const [showNoResults, setShowNoResults] = useState(false);
  const [searchSuggestions, setSearchSuggestions] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const dropdownRef = useRef(null);

  const minLength = 3;
  const [sortConfig, setSortConfig] = useState({ key: null, direction: "asc" });
  const [userPage, setUserPage] = useState(1);
  const [rolePage, setRolePage] = useState(1);
  const [llmsPage, setLlmsPage] = useState(1);
  const wrapperRef = useRef(null);
  const [searchTable, setSearchTable] = useState('');
  const [llmsData, setllmsData] = useState([]);
  const [preSetLlms, setllms] = useState([]);
	const [isTableSearchFocused, setIsTableSearchFocused] = useState(false);

  // Define available page sizes
  const pageSizes = [10, 25, 50, 100];
  
  // Function to handle option selection
  const handleOptionSelect = (value) => {
    setPerPage(Number(value));
    setIsDropdownOpen(false);
  };

  // Function to toggle dropdown
  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };
// Hide dropdown on outside click
  useEffect(() => {
    if (!isDropdownOpen) return;
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsDropdownOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [isDropdownOpen]);
  useEffect(() => {
    function handleClickOutside(event) {
      if (
        wrapperRef.current &&
        !wrapperRef.current.contains(event.target)
      ) {
        setShowSuggestions(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);

    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);

  const handleSort = (key) => {
    setSortConfig((prev) => {
      if (prev.key === key) {
        return {
          key,
          direction: prev.direction === "asc" ? "desc" : "asc",
        };
      } else {
        return { key, direction: "asc" };
      }
    });
  };

  const sortedUsers = useMemo(() => {
    let sortable = [...users];
    
    // Apply search filter if term exists
    if (searchTable) {
      const term = searchTable.toLowerCase();
      sortable = sortable.filter(user => 
        user.user_login.toLowerCase().includes(term) || 
        user.user_email.toLowerCase().includes(term) ||
        user.ID.toString().includes(term)
      );
    }
  
    if (sortConfig.key) {
      sortable.sort((a, b) => {
        if (sortConfig.key === "ID") {
          return sortConfig.direction === "asc" ? a.ID - b.ID : b.ID - a.ID;
        }
        // For action field, sort by a computed action value
        if (sortConfig.key === "action") {
          const aAction = `${a.login_redirect || ''}${a.logout_redirect || ''}`;
          const bAction = `${b.login_redirect || ''}${b.logout_redirect || ''}`;
          const aVal = aAction.toLowerCase();
          const bVal = bAction.toLowerCase();
          if (aVal < bVal) return sortConfig.direction === "asc" ? -1 : 1;
          if (aVal > bVal) return sortConfig.direction === "asc" ? 1 : -1;
          return 0;
        }
        // For text fields like user_login, user_email, login_redirect, and logout_redirect
        const aVal = a[sortConfig.key]?.toString().toLowerCase() || '';
        const bVal = b[sortConfig.key]?.toString().toLowerCase() || '';
        if (aVal < bVal) return sortConfig.direction === "asc" ? -1 : 1;
        if (aVal > bVal) return sortConfig.direction === "asc" ? 1 : -1;
        return 0;
      });
    }
    return sortable;
  }, [users, sortConfig, searchTable]);
  
  const sortedRoles = useMemo(() => {
    let sortable = [...roles];
    
    // Apply search filter if term exists
    if (searchTable) {
      const term = searchTable.toLowerCase();
      sortable = sortable.filter(role => {
        const displayName = (role.display_name || role.name || '').toLowerCase();
        const name = (role.name || '').toLowerCase();
        return displayName.includes(term) || name.includes(term);
      });
    }
  
    if (sortConfig.key) {
      sortable.sort((a, b) => {
        if (sortConfig.key === "index") {
          return sortConfig.direction === "asc"
            ? a.index - b.index
            : b.index - a.index;
        }
        // For action field, sort by a computed action value
        if (sortConfig.key === "action") {
          const aAction = `${a.login || ''}${a.logout || ''}`;
          const bAction = `${b.login || ''}${b.logout || ''}`;
          const aVal = aAction.toLowerCase();
          const bVal = bAction.toLowerCase();
          if (aVal < bVal) return sortConfig.direction === "asc" ? -1 : 1;
          if (aVal > bVal) return sortConfig.direction === "asc" ? 1 : -1;
          return 0;
        }
        // For name field, use display_name for sorting (better UX)
        if (sortConfig.key === "name") {
          const aVal = (a.display_name || a.name || '').toString().toLowerCase();
          const bVal = (b.display_name || b.name || '').toString().toLowerCase();
          if (aVal < bVal) return sortConfig.direction === "asc" ? -1 : 1;
          if (aVal > bVal) return sortConfig.direction === "asc" ? 1 : -1;
          return 0;
        }
        // For other text fields like login, and logout
        const aVal = a[sortConfig.key]?.toString().toLowerCase() || '';
        const bVal = b[sortConfig.key]?.toString().toLowerCase() || '';
        if (aVal < bVal) return sortConfig.direction === "asc" ? -1 : 1;
        if (aVal > bVal) return sortConfig.direction === "asc" ? 1 : -1;
        return 0;
      });
    }
    return sortable;
  }, [roles, sortConfig, searchTable]);

  const sortedLlms = useMemo(() => {
    let sortable = [...(preSetLlms || [])];
    
    // Apply search filter if term exists
    if (searchTable) {
      const term = searchTable.toLowerCase();
      sortable = sortable.filter(llms => 
        llms && llms.name && llms.name.toLowerCase().includes(term) ||
        llms && llms.value && llms.value.toLowerCase().includes(term)
      );
    }
  
    if (sortConfig.key) {
      sortable.sort((a, b) => {
        if (sortConfig.key === "name") {
          const aVal = a.name?.toString().toLowerCase() || '';
          const bVal = b.name?.toString().toLowerCase() || '';
          if (aVal < bVal) return sortConfig.direction === "asc" ? -1 : 1;
          if (aVal > bVal) return sortConfig.direction === "asc" ? 1 : -1;
          return 0;
        }
        // For action field, sort by a computed action value
        if (sortConfig.key === "action") {
          const aAction = `${a.login || ''}${a.logout || ''}`;
          const bAction = `${b.login || ''}${b.logout || ''}`;
          const aVal = aAction.toLowerCase();
          const bVal = bAction.toLowerCase();
          if (aVal < bVal) return sortConfig.direction === "asc" ? -1 : 1;
          if (aVal > bVal) return sortConfig.direction === "asc" ? 1 : -1;
          return 0;
        }
        // For other fields like login and logout, use the generic sorting logic
        const aVal = a[sortConfig.key]?.toString().toLowerCase() || '';
        const bVal = b[sortConfig.key]?.toString().toLowerCase() || '';
        if (aVal < bVal) return sortConfig.direction === "asc" ? -1 : 1;
        if (aVal > bVal) return sortConfig.direction === "asc" ? 1 : -1;
        return 0;
      });
    }
    return sortable;
  }, [preSetLlms, sortConfig, searchTable]);

  // Fetch roles when component mounts
  useEffect(() => {
    // Fetch roles from API
    apiFetch({ path: "/loginpress/v1/settings" }).then((response) => {
      const rolesData = Object.values(response.userRoles).map((role) => ({
        name: role.name,
      }));
      setAllRoles(rolesData);
    });
  }, []);

  if ( loginpressEnableLLMS ) {
    // Fetch LLMS data (courses and memberships) when component mounts
    useEffect(() => {
        apiFetch({ path: '/loginpress/v1/llms-data' }).then(response => {
            setllmsData(response);
        });
    }, []);
  }

  // Reset page numbers when search changes
  useEffect(() => {
    setUserPage(1);
    setRolePage(1);
    setLlmsPage(1);
  }, [searchTable]);

  // Clear search when switching tabs
  useEffect(() => {
    setSearchTable("");
  }, [activeTab]);

  // Fetch initial data
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const userResponse = await apiFetch({
          path: "/loginpress/v1/redirect-users",
          method: "GET",
        });
        setUsers(userResponse);
        const roleResponse = await apiFetch({
          path: "/loginpress/v1/redirect-roles",
          method: "GET",
        });
        setRoles(roleResponse);
        if ( loginpressEnableLLMS ) {
          const llmsResponse = await apiFetch({
            path: '/loginpress/v1/redirect-llms',
            method: 'GET'
          });
          setllms(llmsResponse);
        }
      } catch (error) {
        setMessage({
          text: __("Failed to load redirect data.", "loginpress-pro"),
          type: "error",
        });
      } finally {
        setLoading(false);
		setTimeout(function(){
		  setMessage({text: "", type: ""})
		}, 3000);
      }
    };
    fetchData();
  }, []);

  // Handle search input changes
  const handleSearchChange = async (e) => {
    const term = e.target.value;
    setSearchTerm(prev => ({
      ...prev,
      [activeTab]: term
    }));

    if (term.length > 0 && term.length < minLength) {
      setShowHint(true);
      setShowSuggestions(false);
    } else {
      setShowHint(false);
      if (term.length >= minLength) {
        await handleSearch(term);
      } else {
        setShowSuggestions(false);
      }
    }
  };

  // Handle search
  const handleSearch = async (term) => {
    try {
        // setLoading(true);
        if (activeTab === "users") {
          const path = "/loginpress/v1/search-users";

          const response = await apiFetch({
            path: path,
            method: "POST",
            data: {
              search: term,
              security: window.loginpressData?.search_nonce || '',
            },
          });
            if (response.users.length === 0) {
                setShowNoResults(true);
                setTimeout(() => setShowNoResults(false), 2500);
                setShowSuggestions(false);
            } else {
                setSearchSuggestions(response.users || []);
                setShowSuggestions(true);
            }
        } else if (activeTab === 'roles') {
            // Filter roles by search term (case-insensitive)
            const filteredRoles = (allRoles || []).filter(role =>
                role && role.name && role.name.toLowerCase().includes(term.toLowerCase())
            );
            if (filteredRoles.length === 0) {
                setShowNoResults(true);
                setTimeout(() => setShowNoResults(false), 2500);
                setShowSuggestions(false);
            } else {
                setSearchSuggestions(filteredRoles);
                setShowSuggestions(true);
            }
        } else if (activeTab === 'llms') {
            
            const filteredData = (llmsData || []).filter(data =>
            data && data.label && data.label.toLowerCase().includes(term.toLowerCase())
            );
            if (filteredData.length === 0) {
                setShowNoResults(true);
                setTimeout(() => setShowNoResults(false), 2500);
                setShowSuggestions(false);
            } else {
                setSearchSuggestions(filteredData);
                setShowSuggestions(true);
            }
        }
    } catch (error) {
      setMessage({
        text: __("Failed to search.", "loginpress-pro"),
        type: "error",
      });
    } finally {
      // setLoading(false);
	  setTimeout(function(){
		setMessage({text: "", type: ""})
	  }, 3000);
    }
  };

  // Handle user selection from suggestions
  const handleUserSelect = async (user) => {
    setShowSuggestions(false);
    try {
      setLoading(true);

      // Check if user already exists
      const userExists = users.some((u) => u.ID === user.ID);
      if (userExists) {
        const element = document.getElementById(
          `loginpress_redirects_user_id_${user.ID}`
        );
        if (element) {
          element.classList.add("loginpress_user_highlighted");
          setTimeout(() => {
            element.classList.remove("loginpress_user_highlighted");
          }, 3000);
        }
        return;
      }

      // Normalize user into array (if not already)
      const userArray = Array.isArray(user) ? user : [user];

      // Append to existing users
      setUsers((prevUsers) => [...prevUsers, ...userArray]);
      setSearchTerm(prev => ({
        ...prev,
        [activeTab]: ""
      }));
      setShowSuggestions(false);
      setMessage({
        text: __("User added successfully.", "loginpress-pro"),
        type: "success",
      });
    } catch (error) {
      setMessage({
        text: __("Failed to add user.", "loginpress-pro"),
        type: "error",
      });
    } finally {
      setLoading(false);
	  setTimeout(function(){
		setMessage({text: "", type: ""})
	  }, 3000);
    }
  };

  // Handle role selection from suggestions
  const handleRoleSelect = async (role) => {

    setShowSuggestions(false);
    try {
      setLoading(true);
      
      // Normalize role name to slug for comparison (convert to lowercase, replace spaces with underscores)
      const normalizeRoleName = (name) => {
        return name.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_');
      };
      
      const roleSlug = normalizeRoleName(role.name || role.role || role);
      
      // Check if role already exists by comparing normalized slugs
      const roleExists = roles.some((r) => {
        const existingSlug = normalizeRoleName(r.name || r.role || r);
        return existingSlug === roleSlug;
      });
      
      if (roleExists) {
        // Find the existing role to highlight
        const existingRole = roles.find((r) => {
          const existingSlug = normalizeRoleName(r.name || r.role || r);
          return existingSlug === roleSlug;
        });
        const element = document.getElementById(
          `loginpress_redirects_role_${existingRole.name}`
        );
        if (element) {
          element.classList.add("loginpress_user_highlighted");
          setTimeout(() => {
            element.classList.remove("loginpress_user_highlighted");
          }, 3000);
        }
        setMessage({
          text: __("Role already exists in the list.", "loginpress-pro"),
          type: "warning",
        });
        return;
      }

      // Create role object with proper structure
      // The name will be the display name initially, but will be converted to slug when saved
      const roleObject = {
        name: role.name || role.role || role, // This will be converted to slug on save
        display_name: role.name || role.role || role, // Display name for UI
        login: "",
        logout: ""
      };
      
      // Normalize user into array (if not already)
      const roleArray = Array.isArray(roleObject) ? roleObject : [roleObject];

      // Append to existing roles
      setRoles((prevRoles) => [...prevRoles, ...roleArray]);
      setSearchTerm(prev => ({
        ...prev,
        [activeTab]: ""
      }));
      setShowSuggestions(false);
      setMessage({
        text: __("Role added successfully.", "loginpress-pro"),
        type: "success",
      });
    } catch (error) {
      setMessage({
        text: __("Failed to add role.", "loginpress-pro"),
        type: "error",
      });
    } finally {
      setLoading(false);
	  
	  setTimeout(function(){
		setMessage({text: "", type: ""})
	  }, 3000);
    }
  };

  // Handle llms selection from suggestions
  const handleLlmsSelect = async (llms) => {

    if (!loginpressEnableLLMS) return;
    setShowSuggestions(false);
    try {
        setLoading(true);

        // Normalize llms object
        const normalizedLlms = {
          name: llms.label,
          value: llms.value,
          login: llms.login || '',
          logout: llms.logout || ''
        };

        // Check if llms already exists
        const llmsExists = preSetLlms.some(r => r.name === normalizedLlms.name);
        if (llmsExists) {
            const element = document.getElementById(`loginpress_redirects_llms_${normalizedLlms.value}`);
            if (element) {
                element.classList.add('loginpress_user_highlighted');
                setTimeout(() => {
                    element.classList.remove('loginpress_user_highlighted');
                }, 3000);
            }
            return;
        }
        // Normalize llms into array
        const llmsArray = Array.isArray(normalizedLlms) ? normalizedLlms : [normalizedLlms];

        // Append to existing llms
        setllms(prevllms => [...prevllms, ...llmsArray]);
        setSearchTerm(prev => ({
          ...prev,
          [activeTab]: ""
        }));
        setShowSuggestions(false);
        setMessage({
            text: __('Membership/Course added successfully.', 'loginpress-pro'),
            type: 'success'
        });
    } catch (error) {
        setMessage({
            text: __('Failed to add Membership/Course.', 'loginpress-pro'),
            type: 'error'
        });
    } finally {
        setLoading(false);
		setTimeout(function(){
		  setMessage({text: "", type: ""})
		}, 3000);
    }
  };

  // Handle user redirect update
  const handleUserUpdate = async (userId, loginUrl, logoutUrl) => {
    try {
      setLoadingButton({ type: 'update', id: userId, entityType: 'user' });
      await apiFetch({
        path: "/loginpress/v1/user-redirect-update",
        method: "POST",
        data: {
          id: userId,
          login: loginUrl,
          logout: logoutUrl,
        },
      });
      setMessage({
        text: __("User redirects updated successfully.", "loginpress-pro"),
        type: "success",
      });
    } catch (error) {
      setMessage({
        text: __("Failed to update user redirects.", "loginpress-pro"),
        type: "error",
      });
    } finally {
      setLoadingButton(null);
	  setTimeout(function(){
		setMessage({text: "", type: ""})
	  }, 3000);
    }
  };

  // Handle role redirect update
  const handleRoleUpdate = async (role, loginUrl, logoutUrl) => {
    try {
      setLoadingButton({ type: 'update', id: role, entityType: 'role' });
      
      await apiFetch({
        path: "/loginpress/v1/role-redirect-update",
        method: "POST",
        data: {
          role: role,
          login: loginUrl,
          logout: logoutUrl,
        },
      });
      
      // Refresh roles list to get updated data with correct slug and display name
      const roleResponse = await apiFetch({
        path: "/loginpress/v1/redirect-roles",
        method: "GET",
      });
      
      setRoles(roleResponse);
      
      setMessage({
        text: __("Role redirects updated successfully.", "loginpress-pro"),
        type: "success",
      });
    } catch (error) {
      setMessage({
        text: __("Failed to update role redirects.", "loginpress-pro"),
        type: "error",
      });
    } finally {
      setLoadingButton(null);
	  setTimeout(function(){
		setMessage({text: "", type: ""})
	  }, 3000);
    }
  };

  // Handle llms redirect update
  const handleLlmsUpdate = async (llms, loginUrl, logoutUrl, name) => {
    if (!loginpressEnableLLMS) return;
    try {
        setLoadingButton({ type: 'update', id: llms, entityType: 'llms' });
        await apiFetch({
            path: '/loginpress/v1/llms-redirect-update',
            method: 'POST',
            data: {
                llms: llms,
                login: loginUrl,
                logout: logoutUrl,
                name: name
            }
        });
        setMessage({
            text: __('Membership/Course redirects updated successfully.', 'loginpress-pro'),
            type: 'success'
        });
    } catch (error) {
        setMessage({
            text: __('Failed to update Membership/Course redirects.', 'loginpress-pro'),
            type: 'error'
        });
    } finally {
        setLoadingButton(null);
		setTimeout(function(){
		  setMessage({text: "", type: ""})
		}, 3000);
    }
  };

  // Handle delete actions
  const handleDelete = async (type, id) => {
    try {
      setLoadingButton({ type: 'delete', id: id, entityType: type });
      await apiFetch({
        path: "/loginpress/v1/delete-redirect",
        method: "POST",
        data: {
          type: type,
          id: id,
        },
      });

      // Update local state
      if (type === 'user') {
        setUsers(users.filter(user => user.ID !== id));
      } else if (type === 'role') {
        setRoles(roles.filter(role => role.name !== id));
      } else if (type === 'llms') {
        setllms(sortedLlms.filter(llms => llms.value !== id));
      }

      setMessage({
        text: __("Redirect deleted successfully.", "loginpress-pro"),
        type: "success",
      });
    } catch (error) {
      setMessage({
        text: __("Failed to delete redirect.", "loginpress-pro"),
        type: "error",
      });
    } finally {
      setLoadingButton(null);
	  setTimeout(function(){
		setMessage({text: "", type: ""})
	  }, 3000);
    }
  };
  const CloseMe = () =>{
	setMessage({text: "", type: ""})
  }

  // Render message component
  const renderMessage = () => {
	if (!message || !message.text) return null;
  
	return (
	  <div className={`message has-mb-20 ${message.type}`}>
		<p>{message.text}</p>
		<span className='close-me' onClick={() => (setMessage({text: '', type: ''}))}><i className="dashicons dashicons-no-alt"></i></span>
	  </div>
	);
  };
  

  // Render search bar with suggestions dropdown
  const renderSearchBar = () => {
    const inputIds = {
      users: 'loginpress_redirect_user_search',
      roles: 'loginpress_redirect_role_search',
      llms:  'loginpress_redirect_llms_search'
    };
    const labelValues = {
      users: __('Search Username', 'loginpress-pro'),
      roles: __('Search Roles', 'loginpress-pro'),
      llms:  __('Search Course/Membership', 'loginpress-pro')
    };
    const inputPlaceholders = {
      users: __('Type Username', 'loginpress-pro'),
      roles: __('Type Role', 'loginpress-pro'),
      llms:  __('Type Course/Membership', 'loginpress-pro')
    };
    const descriptions = {
      users: __('Search Username For Whom To Apply Redirects', 'loginpress-pro'),
      roles: __('Search Role For Whom To Apply Redirects', 'loginpress-pro'),
      llms:  __('Search Course/Membership For Whom To Apply Redirects', 'loginpress-pro')
    };    const descriptionElement = (
      <p className='description'>
        {descriptions[activeTab]}
      </p>
    );
    const handleSelect = {
      users: handleUserSelect,
      roles: handleRoleSelect,
      llms : handleLlmsSelect,
    };
    const handleSearchItem = {
      users: (item) => searchTerm[activeTab].includes('@') ? item.user_email : item.user_login,
      roles: (item) => item.name,
      llms : (item) =>  item.label,
    };
    return (
      <div className="loginpress-search-container">
        <label
          htmlFor={inputIds[activeTab]}>
          {labelValues[activeTab]}
        </label>

        <div className="search-input-wrapper" ref={wrapperRef}>
          <input
              type="text"
              id={inputIds[activeTab]}
              name={inputIds[activeTab]}
              value={searchTerm[activeTab]}
              onChange={handleSearchChange}
              placeholder={inputPlaceholders[activeTab]}
              className="ui-autocomplete-input"
              style={{ display: "block" }}
          />

              {showSuggestions && searchSuggestions && searchSuggestions.length > 0 && (
               <div className="search-suggestions">
                   {searchSuggestions.map((item, index) => {
                       if (!item) return null;
                       return (
                           <li className="ui-menu-item"
                             key={index}
                             onClick={() => {
                                 const handler = handleSelect[activeTab];
                                 if (handler) {
                                   handler(item);
                                 }
                               }}
                           >
                             {handleSearchItem[activeTab] ? handleSearchItem[activeTab](item) : 'Unknown item'}
                           </li>
                       );
                   })}
               </div>
           )}
           {showHint && (
            <div className="hintMessage">
              {__("Enter at least 3 characters", "loginpress-pro")}
            </div>
           )}
            {showNoResults && (
              <div className="noUsersMessage">
                {activeTab === "users" && __("No users found", "loginpress-pro")}
                {activeTab === "roles" && __("No roles found", "loginpress-pro")}
                {activeTab === "llms" && __("No courses/memberships found", "loginpress-pro")}
              </div>
            )}
           {descriptionElement}
        </div>

       
      </div>

    );
  };

  // Handle user search
  const handleUserSearch = async () => {
    if (searchTerm[activeTab].length < minLength) return;

    try {
      setLoading(true);
             const response = await apiFetch({
         path: "/loginpress/v1/search-users",
         method: "POST",
         data: {
           search: searchTerm[activeTab],
           security: window.loginpressData?.search_nonce || '',
         },
       });

      if (response.length === 0) {
        setShowNoResults(true);
        setTimeout(() => setShowNoResults(false), 2500);
      } else {
        setSearchSuggestions(response);
        setShowSuggestions(true);
        setShowNoResults(false);
      }
    } catch (error) {
    } finally {
      setLoading(false);
    }
  };

  // Clear table search
	const _clearTableSearch = () => {
    setSearchTable("");
  };

  // Handle role search
  const handleRoleSearch = async () => {
    try {
      setLoading(true);
             const response = await apiFetch({
         path: "/loginpress/v1/search-roles",
         method: "POST",
         data: {
           search: searchTerm[activeTab],
           security: window.loginpressData?.search_nonce || '',
         },
       });

      if (response.length === 0) {
        setShowNoResults(true);
        setTimeout(() => setShowNoResults(false), 2500);
        return;
      }

      // For simplicity, we'll just take the first result
      handleRoleSelect(response[0]);
    } catch (error) {
      setMessage({
        text: __("Failed to search roles.", "loginpress-pro"),
        type: "error",
      });
    } finally {
      setLoading(false);
	  setTimeout(function(){
		setMessage({text: "", type: ""})
	  }, 3000);
    }
  };

  // Render llms table
  const renderLlmsTable = () => (
    <>
    <div className="loginpress-responsive-table-wrapper">
      <table id="loginpress_login_redirect_llms" className="loginpress-responsive-table loginpress_login_redirect_lifterLMA widefat striped dataTable">
          <thead>
            <tr>
                 <th onClick={() => handleSort("membership_course")} className={
                 "sorting" +
                 (sortConfig.key && sortConfig.key === "name"
                   ? sortConfig.direction === "asc"
                     ? " sorting_asc"
                     : " sorting_desc"
                   : "")
               } style={{ cursor: "pointer" }}>
                 {__("Membership/Course", "loginpress-pro")}
               </th>
                  <th onClick={() => handleSort("login")} className={
                 "sorting" +
                 (sortConfig.key && sortConfig.key === "login"
                   ? sortConfig.direction === "asc"
                     ? " sorting_asc"
                     : " sorting_desc"
                   : "")
               } style={{ cursor: "pointer" }}>
                 {__("Login URL", "loginpress-pro")}
               </th>
               <th onClick={() => handleSort("logout")} className={
                 "sorting" +
                 (sortConfig.key && sortConfig.key === "logout"
                   ? sortConfig.direction === "asc"
                     ? " sorting_asc"
                     : " sorting_desc"
                   : "")
               } style={{ cursor: "pointer" }}>
                 {__("Logout URL", "loginpress-pro")}
               </th>
               <th>
                 {__("Action", "loginpress-pro")}
               </th>
            </tr>
          </thead>
          <tbody>
              {loginpressEnableLLMS && paginatedLlms.length > 0 ? (
                  paginatedLlms.map((llms, index) => (
                      <LlmsRow
                          key={index}
                          llms={llms}
                          index={(llmsPage - 1) * perPage + index}
                          onUpdate={handleLlmsUpdate}
                          onDelete={handleDelete}
                          loadingButton={loadingButton}
                      />
                  ))
              ) : (
                  <tr>
                      <td colSpan="4" className="loginpress-empty-data">
                          {searchTable 
                              ? __('No matching records found', 'loginpress-pro')
                              : __('No Membership/Course available', 'loginpress-pro')}
                      </td>
                  </tr>
              )}
          </tbody>
      </table>
    </div>
	<div className="loginpress-pagination-wrapper">
		<div className="showing-of-entries">
		{getEntriesDisplay(llmsPage, perPage, preSetLlms.length, sortedLlms)}
		</div>
		{Math.ceil(sortedLlms.length / perPage) > 1 && (
			<div className="loginpress-pagination">
				<button
					onClick={() => setLlmsPage((p) => Math.max(1, p - 1))}
					disabled={llmsPage === 1 || Math.ceil(sortedLlms.length / perPage) <= 1}
				>
				</button>
				<span>
					{__('Page', 'loginpress-pro')} {llmsPage} {__('of', 'loginpress-pro')}{" "}
					{Math.max(1, Math.ceil(sortedLlms.length / perPage))}
				</span>
				<button
					onClick={() =>
						setLlmsPage((p) =>
							Math.min(Math.ceil(sortedLlms.length / perPage), p + 1)
						)
					}
					disabled={
						llmsPage === Math.ceil(sortedLlms.length / perPage) ||
						sortedLlms.length === 0
					}
				>
				</button>
			</div>
		)}
	</div>
    </>
);

  // Calculate entries display text
  const getEntriesDisplay = (currentPage, perPage, totalItems, filteredItems) => {
    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, filteredItems.length);
    const total = filteredItems.length;
    
    if (total === 0) {
      if (searchTable && searchTable.trim() !== '' && totalItems > 0) {
        return sprintf(__(`Showing %1$d to %2$d of %3$d entries (filtered from %4$d total entries)`, 'loginpress-pro'), start, end, total, totalItems);
      }
      return __('Showing 0 to 0 of 0 entries', 'loginpress-pro');
    }
    
    // Show filtered count when there's a search term (regardless of whether totalItems > total)
    if (searchTable && searchTable.trim() !== '') {
      return sprintf(__(`Showing %1$d to %2$d of %3$d entries (filtered from %4$d total entries)`, 'loginpress-pro'), start, end, total, totalItems);
    }
    
    return sprintf(__(`Showing %1$d to %2$d of %3$d entries`, 'loginpress-pro'), start, end, total);
  };

  // Render users table
  // Render users table with empty state
  const paginatedUsers = useMemo(() => {
    const start = (userPage - 1) * perPage;
    return sortedUsers.slice(start, start + perPage);
  }, [sortedUsers, userPage, perPage]);

  const paginatedRoles = useMemo(() => {
    const start = (rolePage - 1) * perPage;
    return sortedRoles.slice(start, start + perPage);
  }, [sortedRoles, rolePage, perPage]);

  const paginatedLlms = useMemo(() => {
    const start = (llmsPage - 1) * perPage;
    return sortedLlms.slice(start, start + perPage);
  }, [sortedLlms, llmsPage, perPage]);

  const renderUsersTable = () => (
    <>
    <div className="loginpress-table-wrapper">
        <table
        id="loginpress_login_redirect_users"
        className="loginpress-responsive-table loginpress_login_redirect_users widefat fixed striped dataTable"
        >
        <thead>
            <tr>
              <th onClick={() => handleSort("ID")} className={
                "sorting" +
                (sortConfig.key && sortConfig.key === "ID"
                  ? sortConfig.direction === "asc"
                    ? " sorting_asc"
                    : " sorting_desc"
                  : "")
              } style={{ cursor: "pointer" }}>
                {__("User ID", "loginpress-pro")}
              </th>
              <th onClick={() => handleSort("user_login")} className={
                "sorting" +
                (sortConfig.key && sortConfig.key === "user_login"
                  ? sortConfig.direction === "asc"
                    ? " sorting_asc"
                    : " sorting_desc"
                  : "")
              } style={{ cursor: "pointer" }}>
                {__("Username", "loginpress-pro")}
              </th>
              <th onClick={() => handleSort("user_email")} className={
                "sorting" +
                (sortConfig.key && sortConfig.key === "user_email"
                  ? sortConfig.direction === "asc"
                    ? " sorting_asc"
                    : " sorting_desc"
                  : "")
              } style={{ cursor: "pointer" }}>
                {__("Email", "loginpress-pro")}
              </th>
                         <th onClick={() => handleSort("login_redirect")} className={
               "sorting" +
               (sortConfig.key && sortConfig.key === "login_redirect"
                 ? sortConfig.direction === "asc"
                   ? " sorting_asc"
                   : " sorting_desc"
                 : "")
             } style={{ cursor: "pointer" }}>
               {__("Login URL", "loginpress-pro")}
             </th>
             <th onClick={() => handleSort("logout_redirect")} className={
               "sorting" +
               (sortConfig.key && sortConfig.key === "logout_redirect"
                 ? sortConfig.direction === "asc"
                   ? " sorting_asc"
                   : " sorting_desc"
                 : "")
             } style={{ cursor: "pointer" }}>
               {__("Logout URL", "loginpress-pro")}
             </th>
             <th >
               {__("Action", "loginpress-pro")}
             </th>
            </tr>
        </thead>
        <tbody>
        {paginatedUsers.length === 0 ? (
          <tr>
            <td colSpan="6" className="loginpress-empty-data">
              {searchTable ? __('No matching records found', 'loginpress-pro') : __('No Data Available In Table', 'loginpress-pro')}
            </td>
          </tr>
        ) : (
          paginatedUsers.map((user) => (
            <UserRow
              key={user.ID}
              user={user}
              onUpdate={handleUserUpdate}
              onDelete={handleDelete}
              loadingButton={loadingButton}
            />
          ))
        )}
            </tbody>
        </table>
      </div>
      <div className="loginpress-pagination-wrapper">
        <div className="showing-of-entries">
          {getEntriesDisplay(userPage, perPage, users.length, sortedUsers)}
        </div>
        {Math.ceil(sortedUsers.length / perPage) > 1 && (
          <div className="loginpress-pagination">
            <button
              onClick={() => setUserPage((p) => Math.max(1, p - 1))}
              disabled={userPage === 1 || Math.ceil(sortedUsers.length / perPage) <= 1}
            >
            </button>
            <span>
              {__('Page', 'loginpress-pro')} {userPage} {__('of', 'loginpress-pro')}{" "}
              {Math.max(1, Math.ceil(sortedUsers.length / perPage))}
            </span>
            <button
              onClick={() =>
                setUserPage((p) =>
                  Math.min(Math.ceil(sortedUsers.length / perPage), p + 1)
                )
              }
              disabled={
                userPage === Math.ceil(sortedUsers.length / perPage) ||
                sortedUsers.length === 0
              }
            >
            </button>
          </div>
        )}
      </div>
    </>
  );

  const renderRolesTable = () => (
    <>
    <div className="loginpress-table-wrapper">
        <table
            id="loginpress_login_redirect_roles"
            className="loginpress-responsive-table loginpress_login_redirect_roles widefat fixed striped dataTable"
        >
            <thead>
            <tr>
              <th onClick={() => handleSort("index")} className={
                "sorting" +
                (sortConfig.key && sortConfig.key === "index"
                  ? sortConfig.direction === "asc"
                    ? " sorting_asc"
                    : " sorting_desc"
                  : "")
              } style={{ cursor: "pointer" }}>
                {__("No", "loginpress-pro")}
              </th>
              <th onClick={() => handleSort("name")} className={
                "sorting" +
                (sortConfig.key && sortConfig.key === "name"
                  ? sortConfig.direction === "asc"
                    ? " sorting_asc"
                    : " sorting_desc"
                  : "")
              } style={{ cursor: "pointer" }}>
                {__("Role", "loginpress-pro")}
              </th>
                <th onClick={() => handleSort("login")} className={
                  "sorting" +
                  (sortConfig.key && sortConfig.key === "login"
                    ? sortConfig.direction === "asc"
                      ? " sorting_asc"
                      : " sorting_desc"
                    : "")
                } style={{ cursor: "pointer" }}>
                  {__("Login URL", "loginpress-pro")}
                </th>
                                 <th onClick={() => handleSort("logout")} className={
                   "sorting" +
                   (sortConfig.key && sortConfig.key === "logout"
                     ? sortConfig.direction === "asc"
                       ? " sorting_asc"
                       : " sorting_desc"
                     : "")
                 } style={{ cursor: "pointer" }}>
                   {__("Logout URL", "loginpress-pro")}
                 </th>
                 <th>
                   {__("Action", "loginpress-pro")}
                 </th>
            </tr>
            </thead>
            <tbody>
            {paginatedRoles.length === 0 ? (
              <tr>
                <td colSpan="5" className="loginpress-empty-data">
                  {searchTable ? __('No matching records found', 'loginpress-pro') : __('No Data Available In Table', 'loginpress-pro')}
                </td>
              </tr>
            ) : (
              paginatedRoles.map((role, index) => (
                <RoleRow
                  key={role.name}
                  role={{ ...role, index: (rolePage - 1) * perPage + index }}
                  index={(rolePage - 1) * perPage + index}
                  onUpdate={handleRoleUpdate}
                  onDelete={handleDelete}
                  loadingButton={loadingButton}
                />
              ))
            )}
            </tbody>
        </table>
      </div>
      <div className="loginpress-pagination-wrapper">
        <div className="showing-of-entries">
          {getEntriesDisplay(rolePage, perPage, roles.length, sortedRoles)}
        </div>
        {Math.ceil(sortedRoles.length / perPage) > 1 && (
          <div className="loginpress-pagination">
            <button
              onClick={() => setRolePage((p) => Math.max(1, p - 1))}
              disabled={rolePage === 1 || Math.ceil(sortedRoles.length / perPage) <= 1}
            >
            </button>
            <span>
              {__('Page', 'loginpress-pro')} {rolePage} {__('of', 'loginpress-pro')}{" "}
              {Math.max(1, Math.ceil(sortedRoles.length / perPage))}
            </span>
            <button
              onClick={() =>
                setRolePage((p) =>
                  Math.min(Math.ceil(sortedRoles.length / perPage), p + 1)
                )
              }
              disabled={
                rolePage === Math.ceil(sortedRoles.length / perPage) ||
                sortedRoles.length === 0
              }
            >
            </button>
          </div>
        )}
      </div>
    </>
  );

  const renderLlmsTab = () => {

    return (
        <a 
            href="#loginpress_login_redirect_llms"
            className={`loginpress-redirects-tab ${activeTab === 'llms' ? 'loginpress-redirects-active' : ''}`}
            onClick={(e) => {   
                e.preventDefault();
                setActiveTab('llms');
                setSearchTable("");
                setShowHint(false);
                setShowNoResults(false);
                setShowSuggestions(false);
            }}
        >
            {__('LifterLMS Redirects', 'loginpress-pro')}
        </a>
    );
  }

  return (
    <>
      <InfoBox
        heading={__("Login Redirects", "loginpress-pro")}
        description={__("With the Login Redirects add-on, you can easily redirect users based on their roles and specific usernames. Additionally, you can use this add-on to restrict access to certain pages for subscribers, guests, or customers instead of the default wp-admin page.", "loginpress-pro")}
        videoUrl="https://www.youtube.com/watch?v=EYqt8-iegeQ"
		className="loginpress-less-padding"
      />
	  
	  {renderMessage()}
      <div className="loginpress-redirects-container">
        <style>{styles}</style>

        <div className="loginpress-redirects-tab-wrapper">
          <a
            href="#loginpress_login_redirect_users"
            className={`loginpress-redirects-tab ${
              activeTab === "users" ? "loginpress-redirects-active" : ""
            }`}
            onClick={(e) => {
              e.preventDefault();
              setActiveTab("users");
              setSearchTable("");
              setShowHint(false);
              setShowNoResults(false);
              setShowSuggestions(false);
            }}
          >
            {__("Specific Users", "loginpress-pro")}
          </a>
          <a
            href="#loginpress_login_redirect_roles"
            className={`loginpress-redirects-tab ${
              activeTab === "roles" ? "loginpress-redirects-active" : ""
            }`}
            onClick={(e) => {
              e.preventDefault();
              setActiveTab("roles");
              setSearchTable("");
              setShowHint(false);
              setShowNoResults(false);
              setShowSuggestions(false);
            }}
          >
            {__("Specific Roles", "loginpress-pro")}
          </a>
          { loginpressEnableLLMS && renderLlmsTab() }
        </div>
        {renderSearchBar()}
        <div className="loginpress-redirects-header" >
          
          <div className="row-per-page">
            <span>
              {__("Show Entries", "loginpress-pro")}
            </span>
            <div className="select selectbox"  ref={dropdownRef}>
              <select 
                value={perPage}
                onChange={(e) => setPerPage(Number(e.target.value))}
                className="selectbox s-hidden"
              >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <div className="styledSelect" onClick={toggleDropdown}>
                <span className="text-ellipses valueAdded">{perPage}</span>
              </div>
              <ul className="options" style={{ display: isDropdownOpen ? 'block' : 'none' }}>
                {pageSizes.map((size) => (
                  <li 
                    key={size}
                    rel={size}
                    className={perPage === size ? 'active' : ''}
                    onClick={() => handleOptionSelect(size)}
                  >
                    {size}
                  </li>
                ))}
              </ul>
            </div>
          </div>
          <div id="loginpress_login_redirect_users_filter">
            <div className="loginpress_redirect_user_search">
              <input
                type="search"
                placeholder={__("Enter keyword", "loginpress-pro")}
                value={searchTable}
                onChange={(e) => setSearchTable(e.target.value)}
                onFocus={() => setIsTableSearchFocused(true)}
                onBlur={() => setIsTableSearchFocused(false)}
              />
              
              {/* Cross icon (clear button) for table search */}
              {/* {searchTable && (
                <button
                  type="button"
                  className={`clear-table-search-btn ${!isTableSearchFocused ? 'show-on-hover' : ''}`}
                  onClick={clearTableSearch}
                  title={__("Clear search", "loginpress-pro")}
                  style={{color: '#07258c'}}
                >
                  ×
                </button>
              )} */}
            </div>
              <p className="loginpress-description">{__("Search user's data from below the list", "loginpress-pro")}</p>
          </div>
        </div>
            
        <div>
          {/* Always show both tables but control visibility with CSS */}
          <div style={{ display: activeTab === "users" ? "block" : "none" }}>
            {renderUsersTable()}
          </div>
          <div style={{ display: activeTab === "roles" ? "block" : "none" }}>
            {renderRolesTable()}
          </div>
          <div style={{ display: activeTab === 'llms' ? 'block' : 'none' }}>
            { loginpressEnableLLMS && renderLlmsTable() }
          </div>
        </div>
      </div>
    </>
  );
};

// User row component
const UserRow = ({ user, onUpdate, onDelete, loadingButton }) => {
  const [loginUrl, setLoginUrl] = useState(user.login_redirect || "");
  const [logoutUrl, setLogoutUrl] = useState(user.logout_redirect || "");
  
  const isUpdateLoading = loadingButton?.type === 'update' && loadingButton?.id === user.ID && loadingButton?.entityType === 'user';
  const isDeleteLoading = loadingButton?.type === 'delete' && loadingButton?.id === user.ID && loadingButton?.entityType === 'user';

  return (
    <tr
      id={`loginpress_redirects_user_id_${user.ID}`}
      data-login-redirects-user={user.ID}
    >
      <td>
        <div className="lp-tbody-cell">{user.ID}</div>
      </td>
      <td className="loginpress_user_name">
        <div className="lp-tbody-cell">{user.user_login}</div>
      </td>
      <td className="loginpress_login_redirects_email">
        <div className="lp-tbody-cell">{user.user_email}</div>
      </td>
      <td className="loginpress_login_redirects_url">
        <div className="lp-tbody-cell">
          <input
            type="text"
            value={loginUrl}
            onChange={(e) => setLoginUrl(e.target.value)}
            placeholder={__("Enter URL", "loginpress-pro")}
            id="loginpress_login_redirects_url"
          />
        </div>
      </td>
      <td className="loginpress_logout_redirects_url">
        <div className="lp-tbody-cell">
          <input
            type="text"
            value={logoutUrl}
            onChange={(e) => setLogoutUrl(e.target.value)}
            placeholder={__("Enter URL", "loginpress-pro")}
            id="loginpress_logout_redirects_url"
          />
        </div>
      </td>
      <td className="loginpress_login_redirects_actions">
        <div className="lp-tbody-cell">
          <button
            type="button"
            className="button loginpress-user-redirects-update"
            onClick={() => onUpdate(user.ID, loginUrl, logoutUrl)}
            disabled={isUpdateLoading || isDeleteLoading}
          >
            {isUpdateLoading && <span className="loginpress-button-spinner"></span>}
            {__("Update", "loginpress-pro")}
          </button>
          <button
            type="button"
            className="button loginpress-user-redirects-delete"
            onClick={() => onDelete("user", user.ID)}
            disabled={isUpdateLoading || isDeleteLoading}
          >
            {isDeleteLoading && <span className="loginpress-button-spinner"></span>}
            {__("Delete", "loginpress-pro")}
          </button>
        </div>
      </td>
    </tr>
  );
};

// Role row component
const RoleRow = ({ role, index, onUpdate, onDelete, loadingButton }) => {
  const [loginUrl, setLoginUrl] = useState(role.login || "");
  const [logoutUrl, setLogoutUrl] = useState(role.logout || "");
  
  // Use the stored key (name) for operations, which could be either display name (before save) or slug (after save)
  const roleKey = role.name;
  
  const isUpdateLoading = loadingButton?.type === 'update' && loadingButton?.id === roleKey && loadingButton?.entityType === 'role';
  const isDeleteLoading = loadingButton?.type === 'delete' && loadingButton?.id === roleKey && loadingButton?.entityType === 'role';
  
  // Update local state when role prop changes (e.g., after refresh)
  useEffect(() => {
    setLoginUrl(role.login || "");
    setLogoutUrl(role.logout || "");
  }, [role.login, role.logout]);

  return (
    <tr
      id={`loginpress_redirects_role_${roleKey}`}
      data-login-redirects-role={roleKey}
    >
      <td className="loginpress_login_redirects_role sorting_1">
        <div className="lp-tbody-cell no-of">{index + 1}</div>
      </td>
      <td className="loginpress_user_name">
        <div className="lp-tbody-cell">{role.display_name || role.name}</div>
      </td>
      <td className="loginpress_login_redirects_url">
        <div className="lp-tbody-cell">
          <input
            type="text"
            value={loginUrl}
            onChange={(e) => setLoginUrl(e.target.value)}
            placeholder={__("Enter URL", "loginpress-pro")}
            id="loginpress_login_redirects_url"
          />
        </div>
      </td>
      <td className="loginpress_logout_redirects_url">
        <div className="lp-tbody-cell">
          <input
            type="text"
            value={logoutUrl}
            onChange={(e) => setLogoutUrl(e.target.value)}
            placeholder={__("Enter URL", "loginpress-pro")}
            id="loginpress_logout_redirects_url"
          />
        </div>
      </td>
      <td className="loginpress_login_redirects_actions">
        <div className="lp-tbody-cell">
          <button
            type="button"
            className="button loginpress-redirects-role-update"
            onClick={() => onUpdate(roleKey, loginUrl, logoutUrl)}
            disabled={isUpdateLoading || isDeleteLoading}
          >
            {isUpdateLoading && <span className="loginpress-button-spinner"></span>}
            {__("Update", "loginpress-pro")}
          </button>
          <button
            type="button"
            className="button loginpress-redirects-role-delete"
            onClick={() => onDelete("role", roleKey)}
            disabled={isUpdateLoading || isDeleteLoading}
          >
            {isDeleteLoading && <span className="loginpress-button-spinner"></span>}
            {__("Delete", "loginpress-pro")}
          </button>
        </div>
      </td>
    </tr>
  );
};

const LlmsRow = ({ llms, index, onUpdate, onDelete, loadingButton }) => {
  const [loginUrl, setLoginUrl] = useState(llms.login || '');
  const [logoutUrl, setLogoutUrl] = useState(llms.logout || '');
  
  const isUpdateLoading = loadingButton?.type === 'update' && loadingButton?.id === llms.value && loadingButton?.entityType === 'llms';
  const isDeleteLoading = loadingButton?.type === 'delete' && loadingButton?.id === llms.value && loadingButton?.entityType === 'llms';
  
  if ( !loginpressEnableLLMS ) return;
  return (
      <tr id={`loginpress_redirects_llms_${llms.value}`} data-login-redirects-llms={llms.value}>
          <td className="loginpress_user_name"><div className="lp-tbody-cell">{llms.name}</div></td>
          <td className="loginpress_login_redirects_url">
              <div className="lp-tbody-cell">
                  <input
                      type="text"
                      value={loginUrl}
                      onChange={(e) => setLoginUrl(e.target.value)}
                      placeholder={__('Enter URL', 'loginpress-pro')}
                      id="loginpress_login_redirects_url"
                  />
              </div>
          </td>
          <td className="loginpress_logout_redirects_url">
              <div className="lp-tbody-cell">
                  <input
                      type="text"
                      value={logoutUrl}
                      onChange={(e) => setLogoutUrl(e.target.value)}
                      placeholder={__('Enter URL', 'loginpress-pro')}
                      id="loginpress_logout_redirects_url"
                  />
              </div>
          </td>
          <td className="loginpress_login_redirects_actions">
              <div className="lp-tbody-cell">
                  <button
                      type="button"
                      className="button loginpress-redirects-llms-update"
                      onClick={() => onUpdate(llms.value, loginUrl, logoutUrl, llms.name)}
                      disabled={isUpdateLoading || isDeleteLoading}
                  >
                      {isUpdateLoading && <span className="loginpress-button-spinner"></span>}
                      {__('Update', 'loginpress-pro')}
                  </button>
                  <button
                      type="button"
                      className="button loginpress-redirects-llms-delete"
                      onClick={() => onDelete('llms', llms.value)}
                      disabled={isUpdateLoading || isDeleteLoading}
                  >
                      {isDeleteLoading && <span className="loginpress-button-spinner"></span>}
                      {__('Delete', 'loginpress-pro')}
                  </button>
              </div>
          </td>
      </tr>
  );
};

export default LoginRedirects;
